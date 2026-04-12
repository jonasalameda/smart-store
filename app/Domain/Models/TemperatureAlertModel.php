<?php

declare(strict_types=1);

namespace App\Domain\Models;

class TemperatureAlertModel extends BaseModel
{
    public function create(
        int|string $refrigeratorID,
        float $temperature,
        float $threshold,
        string $alertType,
        ?string $message = null
    ): array {
        try {
            if ($message === null) {
                $message = "Temperature alert: {$temperature}°C exceeds threshold of {$threshold}°C";
            }

            $affected = $this->execute(
                'INSERT INTO TemperatureAlerts
                (RefrigeratorID, Temperature, Threshold, AlertType, Message)
                VALUES (:refrigeratorID, :temperature, :threshold, :alertType, :message)',
                [
                    ':refrigeratorID' => $refrigeratorID,
                    ':temperature' => $temperature,
                    ':threshold' => $threshold,
                    ':alertType' => $alertType,
                    ':message' => $message,
                ]
            );

            if ($affected > 0) {
                $id = $this->lastInsertId();
                return ['success' => true, 'message' => 'Alert created successfully', 'id' => $id];
            }

            return ['success' => false, 'message' => 'Failed to create alert'];
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }

    public function getActiveAlerts(): array
    {
        try {
            $stmt = $this->pdo->query(
                'SELECT ta.*, r.Name AS RefrigeratorName, r.Location
                FROM TemperatureAlerts ta
                JOIN Refrigerators r ON ta.RefrigeratorID = r.RefrigeratorID
                WHERE ta.ResolvedAt IS NULL
                ORDER BY ta.AlertTime DESC'
            );

            return ['success' => true, 'data' => $stmt->fetchAll()];
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }

    public function updateUserResponse(int|string $alertID, string $response): array
    {
        try {
            $affected = $this->execute(
                'UPDATE TemperatureAlerts SET UserResponse = :response WHERE AlertID = :alertID',
                [':response' => $response, ':alertID' => $alertID]
            );

            if ($affected > 0) {
                return ['success' => true, 'message' => 'Response updated successfully'];
            }

            return ['success' => false, 'message' => 'Failed to update response'];
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }

    public function markEmailSent(int|string $alertID): array
    {
        try {
            $this->execute(
                'UPDATE TemperatureAlerts SET EmailSent = TRUE WHERE AlertID = :alertID',
                [':alertID' => $alertID]
            );

            return ['success' => true, 'message' => 'Email status updated'];
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }

    public function activateFan(int|string $alertID): array
    {
        try {
            $this->execute(
                'UPDATE TemperatureAlerts SET FanActivated = TRUE WHERE AlertID = :alertID',
                [':alertID' => $alertID]
            );

            return ['success' => true, 'message' => 'Fan activated'];
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }

    public function resolveAlert(int|string $alertID): array
    {
        try {
            $affected = $this->execute(
                'UPDATE TemperatureAlerts SET ResolvedAt = NOW() WHERE AlertID = :alertID',
                [':alertID' => $alertID]
            );

            if ($affected > 0) {
                return ['success' => true, 'message' => 'Alert resolved'];
            }

            return ['success' => false, 'message' => 'Failed to resolve alert'];
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }

    /**
     * @return array|false
     */
    public function findWithRefrigeratorByAlertId(int|string $alertID): array|false
    {
        return $this->selectOne(
            'SELECT ta.RefrigeratorID, ta.Temperature, ta.Threshold, r.Name
            FROM TemperatureAlerts ta
            JOIN Refrigerators r ON ta.RefrigeratorID = r.RefrigeratorID
            WHERE ta.AlertID = :alertID',
            [':alertID' => $alertID]
        );
    }
}
