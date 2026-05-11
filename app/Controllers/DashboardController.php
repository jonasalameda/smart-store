<?php

declare(strict_types=1);

namespace App\Controllers;

use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Domain\Models\HardwareModel;
use App\Domain\Models\RefrigeratorModel;
use App\Domain\Models\SensorReadingModel;
use App\Domain\Models\SystemNotificationModel;
use App\Domain\Models\TemperatureAlertModel;
use App\Domain\Services\MqttService;
use App\Helpers\EmailHelper;

class DashboardController extends BaseController
{
    /**
     * MQTT topic -> refrigerator id. Keeps us off the DB when the table
     * has not been migrated yet; any new fridge must be added here and in
     * the Refrigerators seed.
     */
    private const TOPIC_TO_ID = [
        'Frig1' => 1,
        'Frig2' => 2,
    ];

    public function __construct(
        Container $container,
        private HardwareModel $hardware_model,
        private EmailHelper $email_helper,
        private MqttService $mqtt_service,
        private RefrigeratorModel $refrigerator_model,
        private SensorReadingModel $sensor_reading_model,
        private TemperatureAlertModel $alert_model,
        private SystemNotificationModel $notification_model
    ) {
        parent::__construct($container);
    }

    public function index(Request $request, Response $response, array $args): Response
    {
        // Fetch data from MQTT topics
        $defaults = [
            'Frig1' => ['temperature' => 25, 'humidity' => 60],
            'Frig2' => ['temperature' => 22, 'humidity' => 55],
        ];

        $fridge_data = [];
        foreach (['Frig1', 'Frig2'] as $topic) {
            $message = $this->mqtt_service->getLatestMessage($topic);
            $data = $message ? json_decode($message, true) : null;
            $fridge_data[$topic] = $data ?: $defaults[$topic];
        }

        // Replace any null values with defaults
        foreach ($fridge_data as $fridge => $values) {
            if (!isset($defaults[$fridge])) continue;
            foreach ($values as $key => $value) {
                if ($value === null && isset($defaults[$fridge][$key])) {
                    $fridge_data[$fridge][$key] = $defaults[$fridge][$key];
                }
            }
        }

        $refrigerators = $this->refrigerator_model->getAll();

        $data['data'] = [
            'title' => 'Fridge Dashboard',
            'fridge_data' => $fridge_data,
            'refrigerators' => $refrigerators,
            'flash' => $_SESSION['dashboard_flash'] ?? null,
        ];
        unset($_SESSION['dashboard_flash']);

        return $this->render($response, 'Dashboard.php', $data);
    }

    /**
     * Live fridge status endpoint for the dashboard JS poller.
     *
     * Reads sensor values via Python script, persists each reading to the
     * DB, and lets MqttService record any threshold-breach notifications.
     * Returns the same {"Frig1":..., "Frig2":...} shape the frontend expects.
     */
    public function status(Request $request, Response $response): Response
    {
        // Fetch data from MQTT topics
        $defaults = [
            'Frig1' => ['temperature' => 25, 'humidity' => 60],
            'Frig2' => ['temperature' => 22, 'humidity' => 55],
        ];

        $fridge_data = [];
        foreach (['Frig1', 'Frig2'] as $topic) {
            $message = $this->mqtt_service->getLatestMessage($topic);
            $data = $message ? json_decode($message, true) : null;
            $fridge_data[$topic] = $data ?: $defaults[$topic];
        }

        // Replace any null values with defaults
        foreach ($fridge_data as $fridge => $values) {
            if (!isset($defaults[$fridge])) continue;
            foreach ($values as $key => $value) {
                if ($value === null && isset($defaults[$fridge][$key])) {
                    $fridge_data[$fridge][$key] = $defaults[$fridge][$key];
                }
            }
        }

        $thresholds_path = APP_BASE_DIR_PATH . '/public/assets/other_data/thresholds.json';
        $fallback_thresholds = is_readable($thresholds_path)
            ? (json_decode((string) file_get_contents($thresholds_path), true) ?? [])
            : [];

        foreach (self::TOPIC_TO_ID as $topic => $fridgeId) {
            $reading = $fridge_data[$topic] ?? null;
            if (!is_array($reading)) {
                continue;
            }

            $temperature = $this->toFloatOrNull($reading['temperature'] ?? null);
            $humidity = $this->toFloatOrNull($reading['humidity'] ?? null);
            if ($temperature === null || $humidity === null) {
                continue;
            }

            try {
                $this->sensor_reading_model->create($fridgeId, $temperature, $humidity);
            } catch (\Throwable $e) {
                error_log('DashboardController::status sensor persist: ' . $e->getMessage());
            }

            try {
                $this->mqtt_service->checkAlertsForRefrigerator($fridgeId, $temperature, $humidity);
            } catch (\Throwable $e) {
                error_log('DashboardController::status checkAlerts: ' . $e->getMessage());
            }
        }

        $fridge_data['thresholds'] = $fallback_thresholds;

        $response->getBody()->write((string) json_encode($fridge_data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Send alert email if fridge temperature exceeds threshold
     *
     * Reads thresholds from the database (single source of truth), checks for
     * recent alerts to avoid duplicate emails, then sends if threshold exceeded.
     */
    public function sendAlert(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams(); // JS calls this endpoint with query params
        $fridge_number = $params['fridge'] ?? null;
        $current_temp = $params['temp'] ?? null;

        $fridge_key = 'Frig' . $fridge_number;
        $fridge_id = self::TOPIC_TO_ID[$fridge_key] ?? (int) $fridge_number;

        // Get threshold from database (single source of truth)
        $fridge = $this->refrigerator_model->getById($fridge_id);
        if (!$fridge) {
            $response->getBody()->write(json_encode([
                'status' => 'failure',
                'message' => "Fridge {$fridge_number} not found",
                'email_sent' => false,
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $temp_threshold = (float) ($fridge['Temperature_Threshold'] ?? 25);
        $emailStatus = false;

        // Check if email should be sent: temp exceeds threshold AND no recent alert
        if ($current_temp !== null && $current_temp >= $temp_threshold) {
            $recentAlertExists = $this->hasRecentAlert($fridge_id);
            if (!$recentAlertExists) {
                $emailStatus = $this->email_helper->sendEmail(
                    "polaco.daora@gmail.com", // replace with real recipient
                    "Temperature Alert - Fridge {$fridge_number}",
                    "The current temperature in Fridge {$fridge_number} is {$current_temp}°C. Would you like to turn on the fan?"
                );
            }
        }


        if ($emailStatus) {
            try {
                $alert_id = $this->alert_model->create(
                    $fridge_id,
                    (float) $current_temp,
                    (float) $temp_threshold,
                    TemperatureAlertModel::TYPE_TEMPERATURE,
                    "Temperature alert: {$current_temp}°C exceeds threshold of {$temp_threshold}°C in Fridge {$fridge_number}"
                );
                $this->alert_model->markEmailSent($alert_id);
                $this->notification_model->create(
                    "Fridge {$fridge_number} Temperature Alert",
                    "Email sent: current temperature {$current_temp}°C exceeds threshold {$temp_threshold}°C.",
                    SystemNotificationModel::TYPE_WARNING
                );
            } catch (\Throwable $e) {
                error_log('DashboardController::sendAlert tracking: ' . $e->getMessage());
            }
        }

        /*
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => "Alert sent for Fridge {$fridge_number}",
            'system_notification' => [
                'title' => "Fridge {$fridge_number} Alert",
                'body' => $current_temp !== null
                    ? "Temperature: {$current_temp}°C — Would you like to turn on the fan?"
                    : "Humidity: {$current_humidity}% — Would you like to turn on the fan?",
            ]
        ]));
        return $response->withHeader('Content-Type', 'application/json');
        */

        $response->getBody()->write(json_encode([
            'status' => $emailStatus ? 'success' : 'failure',
            'message' => $emailStatus
                ? "Email alert sent for Fridge {$fridge_number}"
                : "Email alert failed for Fridge {$fridge_number}",
            'email_sent' => $emailStatus,
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Check if user replied to email alert
     */
    public function checkReply(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams(); // from JS query params
        $fridge_number = $params['fridge'];

        // Check if user replied YES to email alert
        $replied_yes = $this->email_helper->readReply("Temperature Alert - Fridge {$fridge_number}");

        if ($replied_yes) {
            $this->activateFanGPIO($fridge_number);

            try {
                $fridge_key = 'Frig' . $fridge_number;
                $fridge_id = self::TOPIC_TO_ID[$fridge_key] ?? (int) $fridge_number;
                $latest_id = $this->latestAlertIdForFridge($fridge_id);
                if ($latest_id !== null) {
                    $this->alert_model->updateUserResponse($latest_id, TemperatureAlertModel::RESPONSE_YES);
                    $this->alert_model->activateFan($latest_id);
                }
                $this->refrigerator_model->updateFanStatusForAll('ON');
                $this->notification_model->create(
                    "Fan Activated (Fridge {$fridge_number})",
                    "Fan turned ON for Fridge {$fridge_number} in response to email reply.",
                    SystemNotificationModel::TYPE_SUCCESS
                );
            } catch (\Throwable $e) {
                error_log('DashboardController::checkReply tracking: ' . $e->getMessage());
            }
        }

        $response->getBody()->write(json_encode([
            'reply' => $replied_yes ? 'yes, turn fan ON' : 'no, leave fan OFF',
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Manually toggle the fan ON or OFF via dashboard button
     * Accepts query param: state=on|off
     */
    public function toggleFan(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $state = $params['state'] ?? 'off'; // default OFF
        $turnOn = $state === 'on';

        $hardwareOk = $this->setFanHardwareState($turnOn);
        $status = $turnOn ? 'Fan turned ON' : 'Fan turned OFF';
        $normalized = $turnOn ? 'ON' : 'OFF';


        try {
            $this->refrigerator_model->updateFanStatusForAll($normalized);
            $this->notification_model->create(
                "Fan {$normalized}",
                "Fan was toggled {$normalized} from the dashboard.",
                $normalized === 'ON' ? SystemNotificationModel::TYPE_SUCCESS : SystemNotificationModel::TYPE_INFO
            );
        } catch (\Throwable $e) {
            error_log('DashboardController::toggleFan tracking: ' . $e->getMessage());
        }

        $response->getBody()->write(json_encode([
            'status' => $hardwareOk ? 'success' : 'failure',
            'message' => $hardwareOk ? $status : 'Fan command failed (check python3/gpio permissions).',
            'fan_state' => $state,
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * POST /dashboard/thresholds — persist per-fridge temperature/humidity
     * thresholds submitted from the dashboard settings form.
     *
     * Expects body:
     *   temp_threshold[<id>]=float
     *   humidity_threshold[<id>]=float
     */
    public function updateThresholds(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $temp_map = (array) ($body['temp_threshold'] ?? []);
        $hum_map = (array) ($body['humidity_threshold'] ?? []);

        $updated = 0;
        foreach ($temp_map as $id => $temp) {
            $fridge_id = (int) $id;
            if ($fridge_id <= 0) {
                continue;
            }
            $temp_val = $this->toFloatOrNull($temp);
            $hum_val = $this->toFloatOrNull($hum_map[$id] ?? null);
            if ($temp_val === null || $hum_val === null) {
                continue;
            }

            try {
                $this->refrigerator_model->updateThresholds($fridge_id, $temp_val, $hum_val);
                $updated++;
            } catch (\Throwable $e) {
                error_log('DashboardController::updateThresholds: ' . $e->getMessage());
            }
        }

        if ($updated > 0) {
            try {
                $this->notification_model->create(
                    'Thresholds Updated',
                    "Updated thresholds for {$updated} refrigerator(s).",
                    SystemNotificationModel::TYPE_INFO
                );
            } catch (\Throwable $e) {
                error_log('DashboardController::updateThresholds notif: ' . $e->getMessage());
            }
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $_SESSION['dashboard_flash'] = [
            'type' => $updated > 0 ? 'success' : 'error',
            'message' => $updated > 0
                ? "Thresholds updated for {$updated} refrigerator(s)."
                : 'No thresholds were updated.',
        ];

        $base = defined('APP_ROOT_DIR_NAME') ? '/' . APP_ROOT_DIR_NAME : '';
        return $response->withStatus(302)->withHeader('Location', $base . '/dashboard');
    }

    /**
     * Activate fan for a fridge via GPIO
     * * Control shared fan GPIO pins
     *
     * Controls the shared DC motor fan via Raspberry Pi GPIO pins.
     * Uses L293D motor driver for direction and speed control.
     *
     *  * GPIO Pin Configuration:
     * - Enable (GPIO 22): Controls motor power
     * - IN1 (GPIO 27): Direction control (forward)
     * - IN2 (GPIO 17): Direction control (reverse)
     */
    private function activateFanGPIO(string $fridge_number = 'shared'): void
    {
        // Map fridge to GPIO pins if needed
        $pins = [
            'enable' => 22,
            'in1' => 27,
            'in2' => 17,
        ];



        // Turn fan ON
        shell_exec("gpio -g mode {$pins['in1']} out");
        shell_exec("gpio -g mode {$pins['in2']} out");
        shell_exec("gpio -g mode {$pins['enable']} out");

        shell_exec("gpio -g write {$pins['in1']} 1");
        shell_exec("gpio -g write {$pins['in2']} 0");
        shell_exec("gpio -g write {$pins['enable']} 1");

        error_log("Fan activated for Fridge {$fridge_number} via GPIO");
    }

    private function setFanHardwareState(bool $turnOn): bool
    {
        $script = APP_BASE_DIR_PATH . '/public/assets/python/fan_motor.py';
        if (is_file($script) && is_readable($script)) {
            $cmd = sprintf(
                'python3 %s %s 2>&1',
                escapeshellarg($script),
                escapeshellarg($turnOn ? 'on' : 'off')
            );
            $output = [];
            $exitCode = 1;
            @exec($cmd, $output, $exitCode);
            if ($exitCode === 0) {
                return true;
            }
            error_log('DashboardController::setFanHardwareState python failed: ' . implode(' | ', $output));
        }

        return $turnOn ? $this->setFanViaGpioOn() : $this->setFanViaGpioOff();
    }

    private function setFanViaGpioOn(): bool
    {
        try {
            $this->activateFanGPIO('shared');
            return true;
        } catch (\Throwable $e) {
            error_log('DashboardController::setFanViaGpioOn: ' . $e->getMessage());
            return false;
        }
    }

    private function setFanViaGpioOff(): bool
    {
        $pins = [
            'enable' => 22,
            'in1' => 27,
            'in2' => 17,
        ];
        $commands = [
            "gpio -g write {$pins['in1']} 0",
            "gpio -g write {$pins['in2']} 0",
            "gpio -g write {$pins['enable']} 0",
        ];
        foreach ($commands as $command) {
            @shell_exec($command . ' 2>&1');
        }
        error_log('Fan deactivated via GPIO');

        return true;
    }

    private function latestAlertIdForFridge(int $fridgeId): ?int
    {
        try {
            $pdo = $this->container->get(\App\Helpers\Core\PDOService::class)->getPDO();
            $stmt = $pdo->prepare(
                'SELECT AlertID FROM TemperatureAlerts
                  WHERE RefrigeratorID = :rid
                  ORDER BY AlertTime DESC
                  LIMIT 1'
            );
            $stmt->execute(['rid' => $fridgeId]);
            $id = $stmt->fetchColumn();
            return $id !== false ? (int) $id : null;
        } catch (\Throwable $e) {
            error_log('latestAlertIdForFridge: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if there's a recent alert for this fridge (within last 1 minute for testing).
     * Prevents duplicate email alerts from being sent too frequently.
     * Set minutesBack to 0 to disable cooldown entirely (useful for testing).
     */
    private function hasRecentAlert(int $fridgeId, int $minutesBack = 1): bool
    {
        // For testing: disable cooldown by setting minutesBack to 0
        if ($minutesBack <= 0) {
            return false;
        }

        try {
            $pdo = $this->container->get(\App\Helpers\Core\PDOService::class)->getPDO();
            $cutoffTime = new \DateTime('now', new \DateTimeZone('UTC'));
            $cutoffTime->modify("-{$minutesBack} minutes");

            $stmt = $pdo->prepare(
                'SELECT COUNT(*) FROM TemperatureAlerts
                  WHERE RefrigeratorID = :rid
                  AND AlertTime >= :cutoff
                  AND EmailSent = 1'
            );
            $stmt->execute([
                'rid' => $fridgeId,
                'cutoff' => $cutoffTime->format('Y-m-d H:i:s')
            ]);
            $count = (int) $stmt->fetchColumn();
            return $count > 0;
        } catch (\Throwable $e) {
            error_log('DashboardController::hasRecentAlert: ' . $e->getMessage());
            return false;
        }
    }

    private function toFloatOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }
        return (float) $value;
    }
}
