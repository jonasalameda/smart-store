<?php

declare(strict_types=1);

use App\Controllers\AboutController;
use App\Controllers\CustomerController;
use App\Controllers\ProductController;
use App\Helpers\DateTimeHelper;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Controllers\HardwareController;

return static function (Slim\App $app): void {

    // Routes without authentication check: /login, /token

    //* ROUTE: GET /
    // $app->get('/', [AboutController::class, 'handleAboutWebService'])->setName("customers.index");
    $app->get('/', [CustomerController::class, 'index'])->setName("customers.index");
    // $app->post('/customers', [AboutController::class, 'handleAboutWebService']);
    $app->post('/customers', [CustomerController::class, 'add'])->setName("customers.add");
    $app->post('/api/hardware/indicate', [HardwareController::class, 'indicate']);
    $app->get('/api/fridge-status', [DashboardController::class, 'status'])->setName('dashboard.status');
    //* NOTE: callback naming pattern: handle<ActionName>, e.g. handleGetPlayers
    //* ROUTE: GET /players
    //$app->get('/players', [PlayersController::class, 'handleGetPlayers']);

    //* ROUTE: GET /ping
    $app->get('/ping', function (Request $request, Response $response, $args) {

        $payload = [
            "greetings" => "Reporting! Hello there!",
            "now" => DateTimeHelper::now(DateTimeHelper::Y_M_D_H_M),
        ];
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR));
        return $response;
    });

    // Example route to test error handling.
    $app->get('/error', function (Request $request, Response $response, $args) {
        throw new \Slim\Exception\HttpNotFoundException($request, "Something went wrong");
    });

    //* ROUTE: GET /phpinfo -> Display PHP configuration (useful for Docker)
    //* Docker URL: http://localhost:8080/phpinfo
    $app->get('/phpinfo', function (Request $request, Response $response, $args) {
        ob_start();
        phpinfo();
        $info = ob_get_clean();
        $response->getBody()->write($info);
        return $response->withHeader('Content-Type', 'text/html');
    });

    $app->get('/api/products/read-rfid', [ProductController::class, 'readRfid']);
};
