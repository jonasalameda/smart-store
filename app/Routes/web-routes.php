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
use App\Controllers\CheckoutController;
use App\Controllers\ProductController;
use App\Controllers\AccountController;
use App\Controllers\AdminAuthController;
use App\Controllers\LocaleController;
use App\Controllers\ReportController;
use App\Helpers\Core\AppSettings;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


return static function (Slim\App $app): void {


    //* NOTE: Route naming pattern: [controller_name].[method_name]
    $app->get('/', function (Request $request, Response $response) use ($app): Response {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $prefix = '/' . trim((string) APP_ROOT_DIR_NAME, '/');
        $prefix = $prefix === '/' ? '' : $prefix;
        if (!empty($_SESSION['customer_account']['id'])) {
            return $response->withStatus(302)->withHeader('Location', $prefix . '/dashboard');
        }

        $container = $app->getContainer();
        if ($container !== null) {
            $features = $container->get(AppSettings::class)->get('features');
            if (!(bool) ($features['customer_auth_enabled'] ?? true)) {
                return $response->withStatus(302)->withHeader('Location', $prefix . '/dashboard');
            }
        }

        return $response->withStatus(302)->withHeader('Location', $prefix . '/account/login');
    })->setName('home');
    $app->get('/customers', [CustomerController::class, 'index'])
        ->setName('customers.index');

    $app->get('/admin/login', [AdminAuthController::class, 'loginForm'])->setName('admin.login.form');
    $app->post('/admin/login', [AdminAuthController::class, 'login']);
    $app->get('/admin/logout', [AdminAuthController::class, 'logout'])->setName('admin.logout');


         // Dashboard page route
    $app->get('/dashboard', [DashboardController::class, 'index'])
        ->setName('dashboard.index');

    $app->get('/notifications', [NotificationController::class, 'index'])
        ->setName('notifications.index');
    $app->get('/api/notification-count', [NotificationController::class, 'getCount'])
        ->setName('notifications.count');
    $app->post('/api/notifications/mark-read', [NotificationController::class, 'markRead'])
        ->setName('notifications.markRead');

    $app->post('/dashboard/thresholds', [DashboardController::class, 'updateThresholds'])
        ->setName('dashboard.thresholds.update');
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
    $app->get('/api/products/read-rfid', [ProductController::class, 'readRfid']);
    $app->get('/api/products/by-upc', [ProductController::class, 'apiByUpc']);
    $app->get('/api/products/by-epc', [ProductController::class, 'apiByEpc']);
    $app->get('/api/products/stream-rfid', [ProductController::class, 'streamRfid']);


    $app->post('/customers/delete/{id}', [CustomerController::class, 'handleDeleteCustomer']);
    $app->get('/api/fridge-status', [DashboardController::class, 'status'])->setName('dashboard.status');

    // Phase 3 — products, inventory, shop customer accounts (separate from legacy `customers` CRUD)
    $app->get('/products', [ProductController::class, 'index'])->setName('products.index');
    $app->get('/products/create', [ProductController::class, 'createForm'])->setName('products.create');
    $app->post('/products', [ProductController::class, 'create']);
    $app->get('/products/{id}/history', [ProductController::class, 'receptionHistory'])->setName('products.history');
    $app->get('/products/{id}/edit', [ProductController::class, 'editForm'])->setName('products.edit');
    $app->post('/products/{id}', [ProductController::class, 'update'])->setName('products.update');
    $app->post('/products/{id}/delete', [ProductController::class, 'delete'])->setName('products.delete');

    $app->get('/inventory', [ProductController::class, 'inventory'])->setName('inventory.index');
    $app->post('/inventory/receive', [ProductController::class, 'receive'])->setName('inventory.receive');
    $app->post('/inventory/adjust', [ProductController::class, 'adjustStock'])->setName('inventory.adjust');
    $app->get('/admin/reports', [ReportController::class, 'adminReports'])->setName('admin.reports');
    $app->post('/admin/reports/thresholds', [ReportController::class, 'saveThresholds'])->setName('admin.reports.thresholds');
    $app->get('/admin/reports/inventory-live', [ReportController::class, 'inventoryLive'])->setName('admin.reports.inventory.live');
    $app->get('/admin/reports/export', [ReportController::class, 'exportCsv'])->setName('admin.reports.export');

    $app->get('/account/login', [AccountController::class, 'loginForm'])->setName('account.login.form');
    $app->post('/account/login', [AccountController::class, 'login']);
    $app->get('/account/register', [AccountController::class, 'registerForm'])->setName('account.register.form');
    $app->post('/account/register', [AccountController::class, 'register']);
    $app->get('/account/logout', [AccountController::class, 'logout'])->setName('account.logout');
    $app->get('/account', [AccountController::class, 'dashboard'])->setName('account.dashboard');
    $app->get('/account/search', [AccountController::class, 'search'])->setName('account.search');
    $app->get('/account/summary', [AccountController::class, 'summary'])->setName('account.summary');
    $app->get('/account/receipts/{id}', [AccountController::class, 'receipt'])->setName('account.receipt');

    $app->get('/locale/switch', [LocaleController::class, 'switch'])->setName('locale.switch');

    // A route to test runtime error handling and custom exceptions.
    $app->get('/error', function (Request $request, Response $response, $args) {
        throw new \Slim\Exception\HttpNotFoundException($request, "Something went wrong");
    });


    //* ---------- Phase 3 endpoints -----------------------------------------------------------------------------------------
    // RFID shelf lookup (optional) — disabled; see ProductController::rfidProducts
    // $app->get('/rfid/products', [ProductController::class, 'rfidProducts'])
    //     ->setName('rfid.products');
    // $app->get('/rfid/products/{rfid}', [ProductController::class, 'rfidProducts'])
    //     ->setName('rfid.products.rfid');

    // Products
    // $app->get('/products', [ProductsController::class, 'index'])
    //     ->setName('products.index');

    // $app->get('/products/{id}', [ProductsController::class, 'show'])
    //     ->setName('products.show');

    // $app->post('/products', [ProductsController::class, 'add'])
    //     ->setName('products.add');

    // $app->post('/products/{id}/edit', [ProductsController::class, 'edit'])
    //     ->setName('products.edit');

    // $app->post('/products/{id}/delete', [ProductsController::class, 'delete'])
    //     ->setName('products.delete');

    // Inventory / Stock
    $app->get('/stock', [ProductController::class, 'stock'])
        ->setName('products.stock');

    $app->get('/stock/{product_id}', [ProductController::class, 'stockByProduct'])
        ->setName('products.stock.show');

    $app->post('/stock/receive', [ProductController::class, 'receiveStock'])
        ->setName('products.stock.receive');

    // Customers
    // $app->get('/customers', [CustomerController::class, 'index'])
    //     ->setName('customers.index');

    // $app->get('/customers/{id}', [CustomerController::class, 'show'])
    //     ->setName('customers.show');

    // $app->post('/customers/login', [CustomerController::class, 'login'])
    //     ->setName('customers.login');

    // $app->post('/customers/{id}/edit', [CustomerController::class, 'edit'])
    //     ->setName('customers.edit');

    // Checkout / Purchases
    $app->get('/checkout', [CheckoutController::class, 'index'])->setName('checkout.index');
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

    // msp01 route
// Proxy for Pareto Anywhere sensor (MSP01 @ c30000455da6/3)
$app->get('/api/pareto-status', function (
    Request $request,
    Response $response
    ) {
    $paretoUrl = 'http://localhost:3001/context/device/c30000455da6/3';

    $ctx = stream_context_create(['http' => [
        'timeout'        => 5,
        'ignore_errors'  => true,
    ]]);

    $raw = @file_get_contents($paretoUrl, false, $ctx);

    if ($raw === false) {
        $response->getBody()->write(json_encode([
            'success' => false,
            'error'   => 'Could not reach Pareto Anywhere on localhost:3001',
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(502);
    }

    $data      = json_decode($raw, true);
    $deviceKey = 'c30000455da6/3';
    $dynamb    = $data['devices'][$deviceKey]['dynamb'] ?? [];

    $payload = [
        'success'         => true,
        'temperature'     => isset($dynamb['temperature'])    ? (float) $dynamb['temperature']    : null,
        'relativeHumidity'=> isset($dynamb['relativeHumidity'])? (float) $dynamb['relativeHumidity'] : null,
        'luminousFlux'    => isset($dynamb['LuminousFlux'])   ? (int)   $dynamb['LuminousFlux']   : null,
        'isMotionDetected'=> isset($dynamb['isMotionDetected']) ? (bool) ($dynamb['isMotionDetected'][0] ?? false) : null,
        'timestamp'       => $dynamb['timestamp'] ?? null,
        'batteryPct'      => $dynamb['batteryPercentage'] ?? null,
    ];

    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json');
});
};
