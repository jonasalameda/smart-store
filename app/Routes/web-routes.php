<?php

declare(strict_types=1);

/**
 * This file contains the routes for the web application.
 */

use App\Controllers\CustomerController;
use App\Controllers\HardwareController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


return static function (Slim\App $app): void {


    //* NOTE: Route naming pattern: [controller_name].[method_name]
    $app->get('/', [CustomerController::class, 'index'])
        ->setName('customers.index');

         // Dashboard page route
    $app->get('/dashboard', [DashboardController::class, 'index'])
        ->setName('dashboard.index');

    $app->post('/customers', [CustomerController::class, 'add'])
        ->setName('customers.add');

    $app->post('/api/hardware/indicate', [HardwareController::class, 'indicate'])
        ->setName('api.hardware.indicate');

    $app->post('/customers/delete/{id}', [CustomerController::class, 'handleDeleteCustomer']);
    // A route to test runtime error handling and custom exceptions.
    $app->get('/error', function (Request $request, Response $response, $args) {
        throw new \Slim\Exception\HttpNotFoundException($request, "Something went wrong");
    });
};
