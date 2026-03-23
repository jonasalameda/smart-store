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
        $fridge_topic = "Frig1";
        $fridge_data = $this->hardware_model->mqttReadAndPublish($fridge_topic);
        
        //pass data if needed
        $data = [
            'title' => 'Fridge Dashboard',
            'fridge_data' => $fridge_data,
        ];


        return $this->render($response, 'Dashboard.php', $data);
    }
}
