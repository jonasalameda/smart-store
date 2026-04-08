<?php

declare(strict_types=1);

namespace App\Controllers;

use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Domain\Models\HardwareModel;
use App\Helpers\EmailHelper;

class DashboardController extends BaseController
{
    public function __construct(
        Container $container,
        private HardwareModel $hardware_model,
        private EmailHelper $email_helper
    ) {
        parent::__construct($container);
    }

    public function index(Request $request, Response $response, array $args): Response
    {
        // $fridge_topic = "Frig1";
        $fridge_data = $this->hardware_model->mqttReadAndPublish();
        
        // Pass data to view if needed
        $data['data'] = [
            'title' => 'Fridge Dashboard',
            'fridge_data' => $fridge_data,
        ];

        return $this->render($response, 'Dashboard.php', $data);
    }

    /**
     * To make the DHT11 info load dynamically via AJAX
     */
    public function status(Request $request, Response $response): Response
    {
        $fridge_data = $this->hardware_model->mqttReadAndPublish();
        $payload = json_encode($fridge_data);
        //threshold should be extracted from threshold.json, not hard codded. extract the threshold only for the temperature
        $threshold_data = json_decode(file_get_contents(APP_BASE_DIR_PATH . '/threshold.json'), true);
        $fridge_data['threshold'] = $threshold_data['temperature_threshold'];

        
        $this->customer_model->sendTemperatureAlert(12, $fridge_data['threshold'], "Frig2");

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Send alert email if fridge temperature exceeds threshold
     */
    public function sendAlert(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams(); // JS calls this endpoint with query params
        $fridge_number = $params['fridge'] ?? null;
        $current_temp = $params['temp'] ?? null;

        // Read thresholds from JSON file
        $thresholds_path = APP_BASE_DIR_PATH . '/public/assets/other_data/thresholds.json';
        $thresholds = json_decode(file_get_contents($thresholds_path), true);
        $fridge_key = 'Frig' . $fridge_number;
        $temp_threshold = $thresholds[$fridge_key]['temp_threshold'] ?? 25;

        $emailStatus = false;

        if ($current_temp !== null && $current_temp >= $temp_threshold) {
            $emailStatus = $this->email_helper->sendEmail(
                "markololo2468@gmail.com", // replace with real recipient
                "Temperature Alert - Fridge {$fridge_number}",
                "The current temperature in Fridge {$fridge_number} is {$current_temp}°C. Would you like to turn on the fan?"
            );
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

    if ($state === 'on') {
        $this->activateFanGPIO(); // turn GPIO fan ON
        $status = 'Fan turned ON';
    } else {
        // Turn GPIO fan OFF (same shared pins)
        $pins = [
            'enable' => 22,
            'in1' => 27,
            'in2' => 17,
        ];
        shell_exec("gpio -g write {$pins['in1']} 0");
        shell_exec("gpio -g write {$pins['in2']} 0");
        shell_exec("gpio -g write {$pins['enable']} 0");
        error_log("Fan deactivated via GPIO");
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
    private function activateFanGPIO(string $fridge_number): void
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

}