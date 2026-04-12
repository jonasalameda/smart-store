<?php

declare(strict_types=1);

namespace App\Domain\Models;

class SensorReadingModel extends BaseModel
{
    public function create(int|string $refrigeratorID, float $temperature, float $humidity): array
    {
        try {
            $affected = $this->execute(
                'INSERT INTO SensorReadings (RefrigeratorID, Temperature, Humidity)
                VALUES (:refrigeratorID, :temperature, :humidity)',
                [
                    ':refrigeratorID' => $refrigeratorID,
                    ':temperature' => $temperature,
                    ':humidity' => $humidity,
                ]
            );

            if ($affected > 0) {
                $id = $this->lastInsertId();
                return ['success' => true, 'message' => 'Reading saved successfully', 'id' => $id];
            }

            return ['success' => false, 'message' => 'Failed to save reading'];
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }

    public function getLatestReadings(null|int|string $refrigeratorID = null): array
    {
        try {
            if ($refrigeratorID !== null) {
                $stmt = $this->pdo->prepare(
                    'SELECT sr.*, r.Name AS RefrigeratorName, r.Location
                    FROM SensorReadings sr
                    JOIN Refrigerators r ON sr.RefrigeratorID = r.RefrigeratorID
                    WHERE sr.RefrigeratorID = :refrigeratorID
                    ORDER BY sr.ReadingTime DESC
                    LIMIT 1'
                );
                $stmt->execute([':refrigeratorID' => $refrigeratorID]);
                $reading = $stmt->fetch();

                return ['success' => true, 'data' => $reading ?: null];
            }

            $stmt = $this->pdo->query(
                'SELECT sr.*, r.Name AS RefrigeratorName, r.Location
                FROM SensorReadings sr
                JOIN Refrigerators r ON sr.RefrigeratorID = r.RefrigeratorID
                ORDER BY sr.ReadingTime DESC'
            );
            return ['success' => true, 'data' => $stmt->fetchAll()];
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }

    public function getReadingsHistory(int|string $refrigeratorID, int $limit = 50): array
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM SensorReadings
                WHERE RefrigeratorID = :refrigeratorID
                ORDER BY ReadingTime DESC
                LIMIT :limit'
            );
            $stmt->bindValue(':refrigeratorID', $refrigeratorID, \PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->execute();

            return ['success' => true, 'data' => $stmt->fetchAll()];
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }

    public function getAverageReadings(int|string $refrigeratorID, int $hours = 24): array
    {
        try {
            $hours = max(1, min(8760, $hours));
            $sql = sprintf(
                'SELECT AVG(Temperature) AS AvgTemperature, AVG(Humidity) AS AvgHumidity, COUNT(*) AS ReadingCount
                FROM SensorReadings
                WHERE RefrigeratorID = :refrigeratorID
                AND ReadingTime >= DATE_SUB(NOW(), INTERVAL %d HOUR)',
                $hours
            );
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':refrigeratorID' => $refrigeratorID]);
            $result = $stmt->fetch();

            return ['success' => true, 'data' => $result];
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred'];
        }
    }
}
