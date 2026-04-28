<?php

declare(strict_types=1);

namespace App\Domain\Models;

/**
 * Sensor reading model.
 *
 * Persists temperature/humidity readings from the fridges and
 * provides simple history/latest lookups keyed by MQTT topic.
 * Ported from SmartStoreIoT/app/Models/SensorReading.php.
 */
class SensorReadingModel extends BaseModel
{
    public function create(int $refrigeratorId, float $temperature, float $humidity): void
    {
        $this->execute(
            'INSERT INTO SensorReadings (RefrigeratorID, Temperature, Humidity)
             VALUES (:rid, :t, :h)',
            [
                'rid' => $refrigeratorId,
                't' => $temperature,
                'h' => $humidity,
            ]
        );
    }

    public function getLatest(string $mqttTopic): array|false
    {
        return $this->selectOne(
            'SELECT sr.*, r.Name AS RefrigeratorName, r.Location
               FROM SensorReadings sr
               JOIN Refrigerators r ON r.RefrigeratorID = sr.RefrigeratorID
              WHERE r.MQTT_Topic = :topic
              ORDER BY sr.ReadingTime DESC
              LIMIT 1',
            ['topic' => $mqttTopic]
        );
    }

    public function getHistory(string $mqttTopic, int $limit = 50): array
    {
        // LIMIT cannot be a named parameter on MySQL prepared statements;
        // clamp to a sane range and inline as an integer.
        $limit = max(1, min(500, $limit));

        return $this->selectAll(
            'SELECT sr.*
               FROM SensorReadings sr
               JOIN Refrigerators r ON r.RefrigeratorID = sr.RefrigeratorID
              WHERE r.MQTT_Topic = :topic
              ORDER BY sr.ReadingTime DESC
              LIMIT ' . $limit,
            ['topic' => $mqttTopic]
        );
    }
}
