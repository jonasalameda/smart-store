<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class NotificationController extends BaseController
{
    public function index(Request $request, Response $response, array $args): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $params = $request->getQueryParams();
        $notifications = $_SESSION['notifications'] ?? [];

        // Support notifications passed via query params (e.g. from popup click)
        if (!empty($params['message']) && !empty($params['type'])) {
            $notifications[] = [
                'type' => $params['type'] === 'error' ? 'error' : 'success',
                'message' => $params['message'],
                'time' => date('M j, Y g:i A'),
                'read' => false,
            ];
        }

        foreach ($notifications as &$n) {
            $n['read'] = true;
        }
        unset($n);
        $_SESSION['notifications'] = $notifications;

        $data = [
            'title' => 'Notifications',
            'notifications' => $notifications,
        ];

        return $this->render($response, 'notificationView.php', $data);
    }
}
