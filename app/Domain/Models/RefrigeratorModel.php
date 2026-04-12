<?php

declare(strict_types=1);

namespace App\Domain\Models;

class RefrigeratorModel extends BaseModel
{
    public function readAll(): array
    {
        try {
            $rows = $this->selectAll('SELECT * FROM Refrigerators ORDER BY RefrigeratorID');
            return ['success' => true, 'data' => $rows];
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }

    public function read(int|string $id): array
    {
        try {
            $refrigerator = $this->selectOne(
                'SELECT * FROM Refrigerators WHERE RefrigeratorID = :id',
                [':id' => $id]
            );

            if ($refrigerator) {
                return ['success' => true, 'data' => $refrigerator];
            }

            return ['success' => false, 'message' => 'Refrigerator not found'];
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }

    public function getIdByMqttTopic(string $topic): ?int
    {
        try {
            $row = $this->selectOne(
                'SELECT RefrigeratorID FROM Refrigerators WHERE MQTT_Topic = :topic',
                [':topic' => $topic]
            );
            return $row ? (int) $row['RefrigeratorID'] : null;
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            return null;
        }
    }

    public function updateThresholds(int|string $id, float $temperatureThreshold, float $humidityThreshold): array
    {
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE Refrigerators
                SET Temperature_Threshold = :tempThreshold, Humidity_Threshold = :humThreshold
                WHERE RefrigeratorID = :id'
            );
            $ok = $stmt->execute([
                ':tempThreshold' => $temperatureThreshold,
                ':humThreshold' => $humidityThreshold,
                ':id' => $id,
            ]);

            if ($ok) {
                return ['success' => true, 'message' => 'Thresholds updated successfully'];
            }

            return ['success' => false, 'message' => 'Failed to update thresholds'];
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }

    public function updateFanStatus(int|string $id, string $status): array
    {
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE Refrigerators SET Fan_Status = :status WHERE RefrigeratorID = :id'
            );
            $ok = $stmt->execute([':status' => $status, ':id' => $id]);

            if ($ok) {
                return ['success' => true, 'message' => 'Fan status updated successfully'];
            }

            return ['success' => false, 'message' => 'Failed to update fan status'];
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }

    public function updateFanStatusForAll(string $status): array
    {
        try {
            $stmt = $this->pdo->prepare('UPDATE Refrigerators SET Fan_Status = ?');
            $ok = $stmt->execute([$status]);

            if ($ok) {
                return ['success' => true, 'message' => 'Fan status updated for all refrigerators'];
            }

            return ['success' => false, 'message' => 'Failed to update fan status'];
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
}
