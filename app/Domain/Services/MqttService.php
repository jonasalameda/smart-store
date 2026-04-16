<?php

declare(strict_types=1);

namespace App\Domain\Services;

use App\Domain\Models\RefrigeratorModel;
use App\Domain\Models\SystemNotificationModel;
use App\Domain\Models\TemperatureAlertModel;
use App\Helpers\Core\AppSettings;
use App\Helpers\EmailHelper;
use PhpMqtt\Client\MqttClient;

class MqttService
{
    private string $server = 'localhost';

    private int $port = 1883;

    public function __construct(
        private EmailHelper $email_helper,
        private TemperatureAlertModel $temperature_alert_model,
        private SystemNotificationModel $system_notification_model,
        private RefrigeratorModel $refrigerator_model,
        private AppSettings $settings,
    ) {
    }

    public function publish(string $topic, string $message): void
    {
        try {
            $clientId = 'smart-store-publisher';
            $mqtt = new MqttClient($this->server, $this->port, $clientId);
            $mqtt->connect();
            $mqtt->publish($topic, $message, 0);
            $mqtt->disconnect();
        } catch (\Throwable $e) {
            error_log('MqttService publish: ' . $e->getMessage());
        }
    }

    public function subscribe(string $topic, callable $callback): void
    {
        $clientId = 'smart-store-subscriber';

        $mqtt = new MqttClient($this->server, $this->port, $clientId);
        $mqtt->connect();
        $mqtt->subscribe($topic, $callback, 0);
        $mqtt->loop(true);
        $mqtt->disconnect();
    }

    public function checkAlertsForRefrigerator(int $refrigeratorID, float $temperature, float $humidity): void
    {
        try {
            $this->checkTemperatureAlerts($refrigeratorID, $temperature, $humidity);
        } catch (\Throwable $e) {
            error_log('MqttService checkAlertsForRefrigerator: ' . $e->getMessage());
        }
    }

    private function checkTemperatureAlerts(int $refrigeratorID, float $temperature, float $humidity): void
    {
        try {
            $ref = $this->refrigerator_model->read($refrigeratorID);
            if (!$ref['success'] || empty($ref['data'])) {
                return;
            }

            $refrigerator = $ref['data'];
            $tempThreshold = (float) $refrigerator['Temperature_Threshold'];
            $humidityThreshold = (float) $refrigerator['Humidity_Threshold'];
            $refrigeratorName = (string) $refrigerator['Name'];

            if ($temperature > $tempThreshold) {
                $this->createTemperatureAlert(
                    $refrigeratorID,
                    $temperature,
                    $tempThreshold,
                    'TEMPERATURE_HIGH',
                    "Temperature alert: {$temperature}°C exceeds threshold of {$tempThreshold}°C in {$refrigeratorName}"
                );
            }

            if ($humidity > $humidityThreshold) {
                $this->createTemperatureAlert(
                    $refrigeratorID,
                    $humidity,
                    $humidityThreshold,
                    'HUMIDITY_HIGH',
                    "Humidity alert: {$humidity}% exceeds threshold of {$humidityThreshold}% in {$refrigeratorName}"
                );
            }
        } catch (\Throwable $e) {
            error_log('Error checking alerts: ' . $e->getMessage());
        }
    }

    private function createTemperatureAlert(
        int $refrigeratorID,
        float $value,
        float $threshold,
        string $alertType,
        string $message
    ): void {
        try {
            $result = $this->temperature_alert_model->create(
                $refrigeratorID,
                $value,
                $threshold,
                $alertType,
                $message
            );

            if (!$result['success'] || empty($result['id'])) {
                return;
            }

            $alertID = (int) $result['id'];

            $ref = $this->refrigerator_model->read($refrigeratorID);
            $refrigeratorName = $ref['success'] ? (string) $ref['data']['Name'] : 'Refrigerator';

            $notifTitle = $alertType === 'HUMIDITY_HIGH' ? 'Humidity alert' : 'Temperature alert';
            $this->system_notification_model->create(
                $notifTitle,
                "{$refrigeratorName}: value {$value} exceeded threshold {$threshold}",
                'WARNING'
            );

            $this->sendEmailAlert($refrigeratorID, $value, $threshold, $alertType, $alertID);
        } catch (\Throwable $e) {
            error_log('Error creating alert: ' . $e->getMessage());
        }
    }

    private function sendEmailAlert(
        int $refrigeratorID,
        float $value,
        float $threshold,
        string $alertType,
        int $alertID
    ): void {
        try {
            $ref = $this->refrigerator_model->read($refrigeratorID);
            if (!$ref['success'] || empty($ref['data'])) {
                return;
            }

            $refrigerator = $ref['data'];
            $refrigeratorName = (string) $refrigerator['Name'];
            $location = (string) $refrigerator['Location'];

            $emailCfg = $this->settings->get('email');
            if (!is_array($emailCfg)) {
                return;
            }

            $to = (string) ($emailCfg['alert_recipient'] ?? $emailCfg['smtp_username'] ?? '');
            if ($to === '') {
                error_log('MqttService: no alert recipient configured; skipping email');
                return;
            }

            $alertLabel = $alertType === 'TEMPERATURE_HIGH' ? 'Temperature' : 'Humidity';
            $unit = $alertType === 'TEMPERATURE_HIGH' ? '°C' : '%';

            $base = defined('APP_BASE_URL') ? rtrim((string) APP_BASE_URL, '/') : '';
            if ($base === '') {
                $base = 'http://localhost/' . APP_ROOT_DIR_NAME;
            }

            $yesUrl = $base . '/api/fan-response?alert_id=' . rawurlencode((string) $alertID) . '&response=YES';
            $noUrl = $base . '/api/fan-response?alert_id=' . rawurlencode((string) $alertID) . '&response=NO';

            $subject = "Smart Store IoT Alert - {$refrigeratorName} ({$alertLabel} exceeded)";

            $body = "Smart Store IoT — {$alertLabel} alert\r\n\r\n"
                . "Refrigerator: {$refrigeratorName}\r\n"
                . "Location: {$location}\r\n"
                . "Current {$alertLabel}: {$value}{$unit}\r\n"
                . "Threshold: {$threshold}{$unit}\r\n"
                . 'Time: ' . date('Y-m-d H:i:s') . "\r\n\r\n"
                . "Turn on the shared fan?\r\n"
                . "YES: {$yesUrl}\r\n"
                . "NO: {$noUrl}\r\n";

            $sent = $this->email_helper->sendEmail($to, $subject, $body);

            if ($sent) {
                $this->temperature_alert_model->markEmailSent($alertID);
                $this->system_notification_model->create(
                    'Email sent',
                    "Alert email sent for {$refrigeratorName}",
                    'SUCCESS'
                );
            }
        } catch (\Throwable $e) {
            error_log('Error sending email: ' . $e->getMessage());
        }
    }
}
