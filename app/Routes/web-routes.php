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

use App\Controllers\ProductController;
use App\Controllers\AccountController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


return static function (Slim\App $app): void {


    //* NOTE: Route naming pattern: [controller_name].[method_name]
    $app->get('/', [CustomerController::class, 'index']);
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


    //* ---------- Phase 3 endpoints -----------------------------------------------------------------------------------------
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
