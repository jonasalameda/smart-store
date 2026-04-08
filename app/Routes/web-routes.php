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
use App\Controllers\ProductsController;
use App\Controllers\CheckoutController;

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

    $app->post('/api/hardware/indicate', [HardwareController::class, 'indicate'])
        ->setName('api.hardware.indicate');

    $app->post('/customers/delete/{id}', [CustomerController::class, 'handleDeleteCustomer']);
    $app->get('/api/fridge-status', [DashboardController::class, 'status'])->setName('dashboard.status');
    // A route to test runtime error handling and custom exceptions.
    $app->get('/error', function (Request $request, Response $response, $args) {
        throw new \Slim\Exception\HttpNotFoundException($request, "Something went wrong");
    });


    //* ---------- Phase 3 endpoints -----------------------------------------------------------------------------------------
    // RFID → products (display; placeholder EPC until external reader is wired)
    $app->get('/rfid/products', [ProductsController::class, 'rfidProducts'])
        ->setName('rfid.products');
    $app->get('/rfid/products/{rfid}', [ProductsController::class, 'rfidProducts'])
        ->setName('rfid.products.rfid');

    // Products
    $app->get('/products', [ProductsController::class, 'index'])
        ->setName('products.index');

    $app->get('/products/{id}', [ProductsController::class, 'show'])
        ->setName('products.show');

    $app->post('/products', [ProductsController::class, 'add'])
        ->setName('products.add');

    $app->post('/products/{id}/edit', [ProductsController::class, 'edit'])
        ->setName('products.edit');

    $app->post('/products/{id}/delete', [ProductsController::class, 'delete'])
        ->setName('products.delete');

    // Inventory / Stock
    $app->get('/stock', [ProductsController::class, 'stock'])
        ->setName('products.stock');

    $app->get('/stock/{product_id}', [ProductsController::class, 'stockByProduct'])
        ->setName('products.stock.show');

    $app->post('/stock/receive', [ProductsController::class, 'receiveStock'])
        ->setName('products.stock.receive');

    // Customers
    $app->get('/customers', [CustomerController::class, 'index'])
        ->setName('customers.index');

    $app->get('/customers/{id}', [CustomerController::class, 'show'])
        ->setName('customers.show');

    $app->post('/customers/login', [CustomerController::class, 'login'])
        ->setName('customers.login');

    $app->post('/customers/{id}/edit', [CustomerController::class, 'edit'])
        ->setName('customers.edit');

    // Checkout / Purchases
    $app->post('/checkout', [CheckoutController::class, 'process'])
        ->setName('checkout.process');

    $app->get('/purchases/{id}', [CheckoutController::class, 'show'])
        ->setName('purchases.show');

    $app->get('/purchases/customer/{customer_id}', [CheckoutController::class, 'history'])
        ->setName('purchases.history');

    // Receipts
    $app->get('/receipts/{purchase_id}', [CheckoutController::class, 'receipt'])
        ->setName('receipts.show');

    $app->post('/receipts/{purchase_id}/send', [CheckoutController::class, 'sendReceipt'])
        ->setName('receipts.send');
};
