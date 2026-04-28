<?php

declare(strict_types=1);

namespace App\Domain\Models;

/**
 * Temperature/humidity alert tracking model.
 *
 * Records each threshold-breach event, the email-sent flag,
 * the user's YES/NO reply, and whether the fan was activated.
 * Ported from SmartStoreIoT/app/Models/TemperatureAlert.php.
 */
class TemperatureAlertModel extends BaseModel
{
    public const TYPE_TEMPERATURE = 'TEMPERATURE_HIGH';
    public const TYPE_HUMIDITY = 'HUMIDITY_HIGH';

    public const RESPONSE_YES = 'YES';
    public const RESPONSE_NO = 'NO';
    public const RESPONSE_PENDING = 'PENDING';

    public function create(
        int $refrigeratorId,
        float $temperature,
        float $threshold,
        string $alertType,
        string $message
    ): int {
        $this->execute(
            'INSERT INTO TemperatureAlerts
                (RefrigeratorID, Temperature, Threshold, AlertType, Message)
             VALUES (:rid, :temp, :thr, :at, :msg)',
            [
                'rid' => $refrigeratorId,
                'temp' => $temperature,
                'thr' => $threshold,
                'at' => $alertType,
                'msg' => $message,
            ]
        );

        return (int) $this->lastInsertId();
    }

    public function markEmailSent(int $alertId): void
    {
        $this->execute(
            'UPDATE TemperatureAlerts SET EmailSent = 1 WHERE AlertID = :id',
            ['id' => $alertId]
        );
    }

    public function updateUserResponse(int $alertId, string $response): void
    {
        $normalized = strtoupper(trim($response));
        if (!in_array($normalized, [self::RESPONSE_YES, self::RESPONSE_NO, self::RESPONSE_PENDING], true)) {
            $normalized = self::RESPONSE_PENDING;
        }

        $this->execute(
            'UPDATE TemperatureAlerts SET UserResponse = :r WHERE AlertID = :id',
            ['r' => $normalized, 'id' => $alertId]
        );
    }

    public function activateFan(int $alertId): void
    {
        $this->execute(
            'UPDATE TemperatureAlerts SET FanActivated = 1 WHERE AlertID = :id',
            ['id' => $alertId]
        );
    }
}
