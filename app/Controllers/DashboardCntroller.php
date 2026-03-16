<?php

declare(strict_types=1);

namespace App\Controllers;

use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DashboardController extends BaseController
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
    }

    public function index(Request $request, Response $response, array $args): Response
    {
       //pass data if needed
        $data = [
            'data' => [
                'title' => 'Fridge Dashboard',
            ]
        ];

       
        return $this->render($response, 'dashboard.php', $data);
    }
}