<?php

declare(strict_types=1);

namespace App\Domain\Models;

class SystemNotificationModel extends BaseModel
{
    public function create(string $title, string $message, string $type = 'INFO'): array
    {
        try {
            $affected = $this->execute(
                'INSERT INTO SystemNotifications (Title, Message, Type) VALUES (:title, :message, :type)',
                [':title' => $title, ':message' => $message, ':type' => $type]
            );

            if ($affected > 0) {
                $id = $this->lastInsertId();
                return ['success' => true, 'message' => 'Notification created successfully', 'id' => $id];
            }

            return ['success' => false, 'message' => 'Failed to create notification'];
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }

    public function getRecent(int $limit = 20): array
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM SystemNotifications ORDER BY CreatedAt DESC LIMIT :limit'
            );
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->execute();

            return ['success' => true, 'data' => $stmt->fetchAll()];
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }

    public function getUnread(): array
    {
        try {
            $stmt = $this->pdo->query(
                'SELECT * FROM SystemNotifications WHERE IsRead = FALSE ORDER BY CreatedAt DESC'
            );

            return ['success' => true, 'data' => $stmt->fetchAll()];
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }

    public function markAsRead(int|string $notificationID): array
    {
        try {
            $affected = $this->execute(
                'UPDATE SystemNotifications SET IsRead = TRUE WHERE NotificationID = :notificationID',
                [':notificationID' => $notificationID]
            );

            if ($affected > 0) {
                return ['success' => true, 'message' => 'Notification marked as read'];
            }

            return ['success' => false, 'message' => 'Failed to mark notification as read'];
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }

    public function markAllAsRead(): array
    {
        try {
            $this->execute('UPDATE SystemNotifications SET IsRead = TRUE WHERE IsRead = FALSE', []);

            return ['success' => true, 'message' => 'All notifications marked as read'];
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }

    public function delete(int|string $notificationID): array
    {
        try {
            $affected = $this->execute(
                'DELETE FROM SystemNotifications WHERE NotificationID = :notificationID',
                [':notificationID' => $notificationID]
            );

            if ($affected > 0) {
                return ['success' => true, 'message' => 'Notification deleted'];
            }

            return ['success' => false, 'message' => 'Failed to delete notification'];
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }

    public function getUnreadCount(): array
    {
        try {
            $count = $this->count('SELECT COUNT(*) FROM SystemNotifications WHERE IsRead = FALSE');

            return ['success' => true, 'count' => $count];
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
}
