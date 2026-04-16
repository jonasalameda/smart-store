<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Models\SystemNotificationModel;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class NotificationController extends BaseController
{
    public function __construct(
        Container $container,
        private SystemNotificationModel $system_notification_model,
    ) {
        parent::__construct($container);
    }

    public function index(Request $request, Response $response, array $args): Response
    {
        $recent = $this->system_notification_model->getRecent(100);
        $rows = $recent['success'] ? $recent['data'] : [];

        $data['data'] = [
            'title' => __('notif_page.title'),
            'notifications' => $rows,
        ];

        return $this->render($response, 'notificationView.php', $data);
    }

    public function getCount(Request $request, Response $response): Response
    {
        $result = $this->system_notification_model->getUnreadCount();
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function markRead(Request $request, Response $response): Response
    {
        try {
            $result = $this->system_notification_model->markAllAsRead();
            if (!is_array($result)) {
                $result = ['success' => false, 'message' => 'Invalid response from model'];
            }
        } catch (\Throwable $e) {
            error_log('NotificationController::markRead: ' . $e->getMessage());
            $result = ['success' => false, 'message' => 'Server error'];
        }

        $response->getBody()->write(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
