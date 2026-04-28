<?php

declare(strict_types=1);

namespace App\Domain\Models;

/**
 * Refrigerator model.
 *
 * Manages refrigerator rows: thresholds, fan state, MQTT topic.
 * Ported from SmartStoreIoT/app/Models/Refrigerator.php into the
 * Slim 4 / BaseModel / PDOService architecture.
 */
class RefrigeratorModel extends BaseModel
{
    public function getAll(): array
    {
        return $this->selectAll('SELECT * FROM Refrigerators ORDER BY RefrigeratorID');
    }

    public function getById(int $id): array|false
    {
        return $this->selectOne(
            'SELECT * FROM Refrigerators WHERE RefrigeratorID = :id LIMIT 1',
            ['id' => $id]
        );
    }

    public function getByMqttTopic(string $topic): array|false
    {
        return $this->selectOne(
            'SELECT * FROM Refrigerators WHERE MQTT_Topic = :topic LIMIT 1',
            ['topic' => $topic]
        );
    }

    public function updateThresholds(int $id, float $tempThreshold, float $humThreshold): void
    {
        $this->execute(
            'UPDATE Refrigerators
                SET Temperature_Threshold = :t, Humidity_Threshold = :h
              WHERE RefrigeratorID = :id',
            [
                't' => $tempThreshold,
                'h' => $humThreshold,
                'id' => $id,
            ]
        );
    }

    public function updateFanStatus(int $id, string $status): void
    {
        $this->execute(
            'UPDATE Refrigerators SET Fan_Status = :s WHERE RefrigeratorID = :id',
            ['s' => $this->normalizeFanStatus($status), 'id' => $id]
        );
    }

    public function updateFanStatusForAll(string $status): void
    {
        $this->execute(
            'UPDATE Refrigerators SET Fan_Status = :s',
            ['s' => $this->normalizeFanStatus($status)]
        );
    }

    private function normalizeFanStatus(string $status): string
    {
        $s = strtoupper(trim($status));
        return $s === 'ON' ? 'ON' : 'OFF';
    }
}
