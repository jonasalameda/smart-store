<?php

declare(strict_types=1);

namespace App\Controllers;

use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HardwareController extends BaseController
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
    }

    public function indicate(Request $request, Response $response, array $args): Response
    {
        $data = $request->getParsedBody();
        $status = $data['status'] ?? null;

        if (!in_array($status, ['success', 'error'])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Invalid status. Use "success" or "error"'
            ]));
            return $response->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        }

        $scriptPath = APP_BASE_DIR_PATH . '/public/assets/python/LED_Buzzer.py';

        if (!file_exists($scriptPath)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'LED_Buzzer.py script not found'
            ]));
            return $response->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
        }

        $command = escapeshellcmd("python3 " . escapeshellarg($scriptPath) . " " . escapeshellarg($status));
        $output = [];
        $returnCode = 0;

        exec($command . " 2>&1", $output, $returnCode);

        if ($returnCode === 0) {
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => ucfirst($status) . ' indication activated',
                'output' => implode("\n", $output)
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => 'Failed to activate hardware indication',
            'error' => implode("\n", $output)
        ]));
        return $response->withStatus(500)
            ->withHeader('Content-Type', 'application/json');
    }
}
