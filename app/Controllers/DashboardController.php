<?php

declare(strict_types=1);

namespace App\Controllers;

use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Domain\Models\HardwareModel;

class DashboardController extends BaseController
{
    public function __construct(Container $container, private HardwareModel $hardware_model)
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
        //threshold should be extracted from threshold.json, not hard codded. extract the threshold only for the temperature
        $threshold_data = json_decode(file_get_contents(APP_BASE_DIR_PATH . '/threshold.json'), true);
        $fridge_data['threshold'] = $threshold_data['temperature_threshold'];

        
        $this->customer_model->sendTemperatureAlert(12, $fridge_data['threshold'], "Frig2");

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
}
