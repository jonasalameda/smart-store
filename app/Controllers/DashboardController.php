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
    public function __construct(Container $container, private HardwareModel $hardware_model, private EmailHelper $email_helper)
    {
        parent::__construct($container);
    }

    public function index(Request $request, Response $response, array $args): Response
    {
        // $fridge_topic = "Frig1";
        $fridge_data = $this->hardware_model->mqttReadAndPublish();
        
        //pass data if needed
        $data['data'] = [
            'title' => 'Fridge Dashboard',
            'fridge_data' => $fridge_data,
        ];


        return $this->render($response, 'Dashboard.php', $data);
    }

    /**
     * To make the DHT11 info load dynamically
     */
    public function status(Request $request, Response $response): Response
    {
        $fridge_data = $this->hardware_model->mqttReadAndPublish();
        $payload = json_encode($fridge_data);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function sendAlert(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams(); //js calls this endpoint with query params 
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

        // $response->getBody()->write(json_encode([
        //     'status' => 'success',
        //     'message' => "Alert sent for Fridge {$fridge_number}",
        //     'system_notification' => [
        //         'title' => "Fridge {$fridge_number} Alert",
        //         'body' => $current_temp !== null
        //             ? "Temperature: {$current_temp}°C — Would you like to turn on the fan?"
        //             : "Humidity: {$current_humidity}% — Would you like to turn on the fan?",
        //     ]
        // ]));
        // return $response->withHeader('Content-Type', 'application/json');

        $response->getBody()->write(json_encode([
            'status' => $emailStatus ? 'success' : 'failure',
            'message' => $emailStatus ? "Email alert sent for Fridge {$fridge_number}" : "Email alert failed for Fridge {$fridge_number}",
            'email_sent' => $emailStatus,
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
