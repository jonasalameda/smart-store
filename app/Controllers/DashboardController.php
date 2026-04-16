<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Models\HardwareModel;
use App\Domain\Models\RefrigeratorModel;
use App\Domain\Models\SensorReadingModel;
use App\Domain\Models\SystemNotificationModel;
use App\Domain\Models\TemperatureAlertModel;
use App\Domain\Services\MqttService;
use App\Helpers\EmailHelper;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DashboardController extends BaseController
{
    public function __construct(
        Container $container,
        private HardwareModel $hardware_model,
        private EmailHelper $email_helper,
        private RefrigeratorModel $refrigerator_model,
        private SensorReadingModel $sensor_reading_model,
        private TemperatureAlertModel $temperature_alert_model,
        private SystemNotificationModel $system_notification_model,
        private MqttService $mqtt_service,
    ) {
        parent::__construct($container);
    }

    public function index(Request $request, Response $response, array $args): Response
    {
        $fridge_data = $this->hardware_model->mqttReadAndPublish();
        $this->persistReadingsAndCheckAlerts($fridge_data);

        $data['data'] = [
            'title' => 'Fridge Dashboard',
            'fridge_data' => $fridge_data,
        ];

        return $this->render($response, 'Dashboard.php', $data);
    }

    /**
     * AJAX: latest fridge readings; persists each poll and merges DB thresholds (JSON fallback).
     */
    public function status(Request $request, Response $response): Response
    {
        $fridge_data = $this->hardware_model->mqttReadAndPublish();
        $this->persistReadingsAndCheckAlerts($fridge_data);

        $fallback = $this->loadThresholdsJsonFallback();

        $payload = [
            'Frig1' => [
                'temperature' => $fridge_data['Frig1']['temperature'] ?? null,
                'humidity' => $fridge_data['Frig1']['humidity'] ?? null,
            ],
            'Frig2' => [
                'temperature' => $fridge_data['Frig2']['temperature'] ?? null,
                'humidity' => $fridge_data['Frig2']['humidity'] ?? null,
            ],
        ];

        foreach (['Frig1' => 1, 'Frig2' => 2] as $topic => $id) {
            $ref = $this->refrigerator_model->read($id);
            if ($ref['success'] && !empty($ref['data'])) {
                $row = $ref['data'];
                $payload[$topic]['temp_threshold'] = (float) $row['Temperature_Threshold'];
                $payload[$topic]['humidity_threshold'] = (float) $row['Humidity_Threshold'];
            } else {
                $payload[$topic]['temp_threshold'] = (float) ($fallback[$topic]['temp_threshold'] ?? 25);
                $payload[$topic]['humidity_threshold'] = (float) ($fallback[$topic]['humidity_threshold'] ?? 70);
            }
        }

        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function sendAlert(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $fridge_number = isset($params['fridge']) ? (int) $params['fridge'] : null;
        $current_temp = isset($params['temp']) ? (float) $params['temp'] : null;
        $current_humidity = isset($params['humidity']) ? (float) $params['humidity'] : null;
        $hasTempReading = array_key_exists('temp', $params);
        $hasHumidityReading = array_key_exists('humidity', $params);

        if ($fridge_number === null || $fridge_number < 1) {
            $response->getBody()->write(json_encode(['status' => 'failure', 'message' => 'Invalid fridge']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $ref = $this->refrigerator_model->read($fridge_number);
        $fallback = $this->loadThresholdsJsonFallback();
        $fridge_key = 'Frig' . $fridge_number;

        $temp_threshold = null;
        $hum_threshold = null;
        if ($ref['success'] && !empty($ref['data'])) {
            $temp_threshold = (float) $ref['data']['Temperature_Threshold'];
            $hum_threshold = (float) $ref['data']['Humidity_Threshold'];
        } else {
            $temp_threshold = (float) ($fallback[$fridge_key]['temp_threshold'] ?? 25);
            $hum_threshold = (float) ($fallback[$fridge_key]['humidity_threshold'] ?? 70);
        }

        $emailStatus = false;
        $emailCfg = $this->settings->get('email');
        $to = is_array($emailCfg)
            ? (string) ($emailCfg['alert_recipient'] ?? $emailCfg['smtp_username'] ?? '')
            : '';

        if ($to !== '' && $hasTempReading && $current_temp >= $temp_threshold) {
            $emailStatus = $this->email_helper->sendEmail(
                $to,
                "Temperature Alert - Fridge {$fridge_number}",
                "The current temperature in Fridge {$fridge_number} is {$current_temp}°C (threshold {$temp_threshold}°C). Would you like to turn on the fan?"
            );
        }

        if ($to !== '' && $hasHumidityReading && $current_humidity >= $hum_threshold) {
            $emailStatus = $this->email_helper->sendEmail(
                $to,
                "Humidity Alert - Fridge {$fridge_number}",
                "The current humidity in Fridge {$fridge_number} is {$current_humidity}% (threshold {$hum_threshold}%). Would you like to turn on the fan?"
            ) || $emailStatus;
        }

        $response->getBody()->write(json_encode([
            'status' => $emailStatus ? 'success' : 'failure',
            'message' => $emailStatus
                ? "Email alert sent for Fridge {$fridge_number}"
                : "Email alert not sent for Fridge {$fridge_number}",
            'email_sent' => $emailStatus,
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function checkReply(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $fridge_number = isset($params['fridge']) ? (string) $params['fridge'] : '1';

        $replied_yes = $this->email_helper->readReply('Temperature Alert - Fridge ' . $fridge_number);

        if ($replied_yes) {
            $this->refrigerator_model->updateFanStatusForAll('ON');
            $this->activateFanGPIO('shared');
            $this->system_notification_model->create(
                'Fan activated',
                'Shared fan turned ON from email reply (IMAP) for Fridge ' . $fridge_number,
                'SUCCESS'
            );
        }

        $response->getBody()->write(json_encode([
            'reply' => $replied_yes ? 'yes, turn fan ON' : 'no, leave fan OFF',
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function toggleFan(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $state = $params['state'] ?? 'off';

        if ($state === 'on') {
            $this->refrigerator_model->updateFanStatusForAll('ON');
            $this->activateFanGPIO('shared');
            $this->system_notification_model->create(
                'Shared fan control',
                'Shared DC motor turned ON',
                'SUCCESS'
            );
            $status = 'Fan turned ON';
        } else {
            $this->refrigerator_model->updateFanStatusForAll('OFF');
            $pins = [
                'enable' => 22,
                'in1' => 27,
                'in2' => 17,
            ];
            shell_exec("gpio -g write {$pins['in1']} 0");
            shell_exec("gpio -g write {$pins['in2']} 0");
            shell_exec("gpio -g write {$pins['enable']} 0");
            error_log('Fan deactivated via GPIO');
            $this->system_notification_model->create(
                'Shared fan control',
                'Shared DC motor turned OFF',
                'INFO'
            );
            $status = 'Fan turned OFF';
        }

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => $status,
            'fan_state' => $state,
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Email link handler: YES/NO for fan after threshold alert email.
     */
    public function fanResponse(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $alertID = isset($params['alert_id']) ? (int) $params['alert_id'] : 0;
        $userResponse = isset($params['response']) ? strtoupper((string) $params['response']) : '';

        if ($alertID < 1 || !in_array($userResponse, ['YES', 'NO'], true)) {
            return $this->render($response, 'FanResponseResult.php', [
                'data' => [
                    'ok' => false,
                    'message' => 'Missing or invalid parameters.',
                ],
            ]);
        }

        $this->temperature_alert_model->updateUserResponse($alertID, $userResponse);

        if ($userResponse === 'YES') {
            $alert = $this->temperature_alert_model->findWithRefrigeratorByAlertId($alertID);
            if ($alert) {
                $this->refrigerator_model->updateFanStatusForAll('ON');
                $this->activateFanGPIO('shared');
                $this->temperature_alert_model->activateFan($alertID);
                $this->system_notification_model->create(
                    'Fan activated',
                    'Shared fan turned ON from email link for ' . ($alert['Name'] ?? 'refrigerator'),
                    'SUCCESS'
                );

                return $this->render($response, 'FanResponseResult.php', [
                    'data' => [
                        'ok' => true,
                        'message' => 'Fan has been activated successfully.',
                        'temperature' => $alert['Temperature'],
                        'threshold' => $alert['Threshold'],
                    ],
                ]);
            }

            return $this->render($response, 'FanResponseResult.php', [
                'data' => ['ok' => false, 'message' => 'Alert not found.'],
            ]);
        }

        return $this->render($response, 'FanResponseResult.php', [
            'data' => [
                'ok' => true,
                'message' => 'No action taken. The fan will not be activated.',
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $fridge_data
     */
    private function persistReadingsAndCheckAlerts(array $fridge_data): void
    {
        $map = [
            'Frig1' => $this->refrigerator_model->getIdByMqttTopic('Frig1'),
            'Frig2' => $this->refrigerator_model->getIdByMqttTopic('Frig2'),
        ];

        foreach ($map as $key => $rid) {
            if ($rid === null) {
                continue;
            }
            $block = $fridge_data[$key] ?? null;
            if (!is_array($block)) {
                continue;
            }
            $temp = isset($block['temperature']) ? (float) $block['temperature'] : null;
            $hum = isset($block['humidity']) ? (float) $block['humidity'] : null;
            if ($temp === null || $hum === null) {
                continue;
            }

            $save = $this->sensor_reading_model->create($rid, $temp, $hum);
            if ($save['success']) {
                $this->mqtt_service->checkAlertsForRefrigerator($rid, $temp, $hum);
            }
        }
    }

    /**
     * @return array<string, array{temp_threshold?: float|int, humidity_threshold?: float|int}>
     */
    private function loadThresholdsJsonFallback(): array
    {
        $path = APP_BASE_DIR_PATH . '/public/assets/other_data/thresholds.json';
        if (!is_readable($path)) {
            return [];
        }
        $raw = file_get_contents($path);
        if ($raw === false) {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function activateFanGPIO(string $fridge_number = 'shared'): void
    {
        $pins = [
            'enable' => 22,
            'in1' => 27,
            'in2' => 17,
        ];

        shell_exec("gpio -g mode {$pins['in1']} out");
        shell_exec("gpio -g mode {$pins['in2']} out");
        shell_exec("gpio -g mode {$pins['enable']} out");

        shell_exec("gpio -g write {$pins['in1']} 1");
        shell_exec("gpio -g write {$pins['in2']} 0");
        shell_exec("gpio -g write {$pins['enable']} 1");

        error_log("Fan activated for Fridge {$fridge_number} via GPIO");
    }
}
