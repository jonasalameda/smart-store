<?php

declare(strict_types=1);

namespace App\Domain\Services;

use App\Domain\Models\RefrigeratorModel;
use App\Domain\Models\SystemNotificationModel;
use PhpMqtt\Client\MqttClient;

class MqttService
{
    private string $server = 'localhost';
    private int $port = 1883;

    public function __construct(
        private RefrigeratorModel $refrigerator_model,
        private SystemNotificationModel $notification_model
    ) {}

    /**
     * This is to publish a message to a topic.
     * Exact publish example from php-mqtt/client README.
     * @param topic the topic name to publish to
     * @param message the message to send for publication
     * @param retain whether to retain the message on the broker
     */
    public function publish(string $topic, string $message, bool $retain = false): void
    {
        $clientId = 'smart-store-publisher';
        $mqtt = new MqttClient($this->server, $this->port, $clientId);
        $mqtt->connect();
        $mqtt->publish($topic, $message, 0, $retain);
        $mqtt->disconnect();
    }

    /**
     * Get the latest retained message for a topic by subscribing briefly.
     * @param topic the topic name to get the latest message from
     * @return the latest message or null if none
     */
    public function getLatestMessage(string $topic): ?string
    {
        $latest = null;
        $callback = function($t, $message) use (&$latest) {
            $latest = $message;
        };
        $clientId = 'smart-store-getter-' . uniqid();
        $mqtt = new MqttClient($this->server, $this->port, $clientId);
        $mqtt->connect();
        $mqtt->subscribe($topic, $callback, 0);
        // Process any retained messages without blocking
        $mqtt->loop(false);
        $mqtt->disconnect();
        return $latest;
    }

    /**
     * Check a refrigerator's latest temperature/humidity against its thresholds
     * and log a SystemNotification if either is exceeded.
     *
     * Alert records (TemperatureAlerts) and outbound email are handled by
     * DashboardController::sendAlert(); this method only adds a passive,
     * server-side trace so the notification feed reflects readings even when
     * the browser is not open.
     */
    public function checkAlertsForRefrigerator(int $refrigeratorId, float $temperature, float $humidity): void
    {
        try {
            $fridge = $this->refrigerator_model->getById($refrigeratorId);
            if (!$fridge) {
                return;
            }

            $tempThreshold = (float) ($fridge['Temperature_Threshold'] ?? 15);
            $humThreshold = (float) ($fridge['Humidity_Threshold'] ?? 40);
            $name = (string) ($fridge['Name'] ?? "Refrigerator {$refrigeratorId}");

            if ($temperature >= $tempThreshold) {
                $this->notification_model->create(
                    'Temperature Alert',
                    "{$name}: {$temperature}°C exceeds threshold of {$tempThreshold}°C",
                    SystemNotificationModel::TYPE_WARNING
                );
            }

            if ($humidity >= $humThreshold) {
                $this->notification_model->create(
                    'Humidity Alert',
                    "{$name}: {$humidity}% exceeds threshold of {$humThreshold}%",
                    SystemNotificationModel::TYPE_WARNING
                );
            }
        } catch (\Throwable $e) {
            error_log('MqttService::checkAlertsForRefrigerator: ' . $e->getMessage());
        }
    }
}

// Source: https://github.com/php-mqtt/client?tab=readme-ov-file
// and: https://github.com/php-mqtt/client-examples

//To test this works on ur computer guys, test this in 2 terminals:

/*
listener terminal
mosquitto_sub -h localhost -t "Frig1"

publisher terminal
mosquitto_pub -h localhost -t "Frig1" -m "temp:43,humidity:100
*/
