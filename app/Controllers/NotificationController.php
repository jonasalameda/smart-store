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
        private SystemNotificationModel $notification_model
    ) {
        parent::__construct($container);
    }

    public function index(Request $request, Response $response, array $args): Response
    {
        $notifications = $this->notification_model->getRecent(50);

        $data['data'] = [
            'title' => __('notif_page.title'),
            'notifications' => $notifications,
        ];

        return $this->render($response, 'notificationView.php', $data);
    }

    public function getCount(Request $request, Response $response): Response
    {
        $count = $this->notification_model->getUnreadCount();

        $response->getBody()->write(json_encode([
            'success' => true,
            'count' => $count,
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->withHeader('Pragma', 'no-cache')
            ->withHeader('Expires', '0');
    }

    public function markRead(Request $request, Response $response): Response
    {
        $this->notification_model->markAllAsRead();

        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
