<?php

declare(strict_types=1);

namespace App\Domain\Models;

/**
 * Persistent system notifications model.
 *
 * Powers the in-app notification feed and the dashboard badge.
 * Ported from SmartStoreIoT/app/Models/SystemNotification.php.
 */
class SystemNotificationModel extends BaseModel
{
    public const TYPE_INFO = 'INFO';
    public const TYPE_WARNING = 'WARNING';
    public const TYPE_ERROR = 'ERROR';
    public const TYPE_SUCCESS = 'SUCCESS';

    public function create(string $title, string $message, string $type = self::TYPE_INFO): void
    {
        $allowed = [self::TYPE_INFO, self::TYPE_WARNING, self::TYPE_ERROR, self::TYPE_SUCCESS];
        $normalized = strtoupper(trim($type));
        if (!in_array($normalized, $allowed, true)) {
            $normalized = self::TYPE_INFO;
        }

        $this->execute(
            'INSERT INTO SystemNotifications (Title, Message, Type)
             VALUES (:t, :m, :ty)',
            [
                't' => $title,
                'm' => $message,
                'ty' => $normalized,
            ]
        );
    }

    public function getRecent(int $limit = 20): array
    {
        $limit = max(1, min(500, $limit));

        return $this->selectAll(
            'SELECT NotificationID, Title, Message, Type, IsRead, CreatedAt
               FROM SystemNotifications
              ORDER BY CreatedAt DESC
              LIMIT ' . $limit
        );
    }

    public function getUnreadCount(): int
    {
        return $this->count(
            'SELECT COUNT(*) FROM SystemNotifications WHERE IsRead = 0'
        );
    }

    public function markAllAsRead(): void
    {
        $this->execute('UPDATE SystemNotifications SET IsRead = 1 WHERE IsRead = 0');
    }
}
