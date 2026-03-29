<?php

declare(strict_types=1);

/**
 * This file contains the routes for the web application.
 */

use App\Controllers\CustomerController;
use App\Controllers\HardwareController;
use App\Controllers\DashboardController;
use App\Controllers\NotificationController;
use App\Controllers\SendAlertController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


return static function (Slim\App $app): void {


    //* NOTE: Route naming pattern: [controller_name].[method_name]
    $app->get('/', [CustomerController::class, 'index'])
        ->setName('customers.index');

         // Dashboard page route
    $app->get('/dashboard', [DashboardController::class, 'index'])
        ->setName('dashboard.index');

    $app->get('/notifications', [NotificationController::class, 'index'])
        ->setName('notifications.index');

    // $app->get('/send-alert', [SendAlertController::class, 'handle'])
        // ->setName('send.alert');
    $app->get('/send-alert', [DashboardController::class, 'sendAlert'])
        ->setName('dashboard.sendAlert');
    
    $app->post('/customers', [CustomerController::class, 'add'])
    ->setName('customers.add');

    $app->get('/api/check-reply', [DashboardController::class, 'checkReply']);

    $app->post('/api/hardware/indicate', [HardwareController::class, 'indicate'])
        ->setName('api.hardware.indicate');

    $app->post('/customers/delete/{id}', [CustomerController::class, 'handleDeleteCustomer']);
    $app->get('/api/fridge-status', [DashboardController::class, 'status'])->setName('dashboard.status');
    // A route to test runtime error handling and custom exceptions.
    $app->get('/error', function (Request $request, Response $response, $args) {
        throw new \Slim\Exception\HttpNotFoundException($request, "Something went wrong");
    });
};
