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
use App\Controllers\ProductController;
use App\Controllers\AccountController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


return static function (Slim\App $app): void {


    //* NOTE: Route naming pattern: [controller_name].[method_name]
    $app->get('/', [CustomerController::class, 'index'])
        ->setName('customers.index');
    $app->get('/customers', [CustomerController::class, 'index'])
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
    
    // FAN toggle route
    $app->get('/toggle-fan', [DashboardController::class, 'toggleFan'])
    ->setName('dashboard.toggleFan');

    $app->get('/api/check-reply', [DashboardController::class, 'checkReply']);

    $app->post('/api/hardware/indicate', [HardwareController::class, 'indicate'])
        ->setName('api.hardware.indicate');

    $app->post('/customers/delete/{id}', [CustomerController::class, 'handleDeleteCustomer']);
    $app->get('/api/fridge-status', [DashboardController::class, 'status'])->setName('dashboard.status');

    // Phase 3 — products, inventory, shop customer accounts (separate from legacy `customers` CRUD)
    $app->get('/products', [ProductController::class, 'index'])->setName('products.index');
    $app->get('/products/create', [ProductController::class, 'createForm'])->setName('products.create');
    $app->post('/products', [ProductController::class, 'create']);
    $app->get('/products/{id}/edit', [ProductController::class, 'editForm'])->setName('products.edit');
    $app->post('/products/{id}', [ProductController::class, 'update'])->setName('products.update');
    $app->post('/products/{id}/delete', [ProductController::class, 'delete'])->setName('products.delete');

    $app->get('/inventory', [ProductController::class, 'inventory'])->setName('inventory.index');
    $app->post('/inventory/receive', [ProductController::class, 'receive'])->setName('inventory.receive');

    $app->get('/account/login', [AccountController::class, 'loginForm'])->setName('account.login.form');
    $app->post('/account/login', [AccountController::class, 'login']);
    $app->get('/account/register', [AccountController::class, 'registerForm'])->setName('account.register.form');
    $app->post('/account/register', [AccountController::class, 'register']);
    $app->get('/account/logout', [AccountController::class, 'logout'])->setName('account.logout');
    $app->get('/account', [AccountController::class, 'dashboard'])->setName('account.dashboard');

    // A route to test runtime error handling and custom exceptions.
    $app->get('/error', function (Request $request, Response $response, $args) {
        throw new \Slim\Exception\HttpNotFoundException($request, "Something went wrong");
    });
};
