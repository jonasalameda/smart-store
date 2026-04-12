<?php

declare(strict_types=1);

namespace App\Domain\Models;

use App\Helpers\Core\PDOService;
use App\Domain\Services\MqttService;

class HardwareModel extends BaseModel
{
    public function __construct(PDOService $pdo_service, private MqttService $mqtt_service)
    {
        parent::__construct($pdo_service);
    }

    /**
     * Reads temperature and humidity via read_serial.py, publishes each fridge payload to MQTT.
     * MQTT publish is wrapped in try/catch so a down broker does not break the request.
     */
    public function mqttReadAndPublish(): array
    {
        $cmd = 'python3 ' . APP_BASE_DIR_PATH . '/public/assets/python/read_serial.py 2>&1';
        $output = shell_exec($cmd);

        if (empty($output)) {
            $output = '{"Frig1":{"temperature":25,"humidity":60},"Frig2":{"temperature":22,"humidity":55}}';
        }

        $defaultData = [
            'Frig1' => ['temperature' => null, 'humidity' => null],
            'Frig2' => ['temperature' => null, 'humidity' => null],
        ];

        $data = json_decode($output, true) ?? $defaultData;

        foreach (['Frig1', 'Frig2'] as $topic) {
            try {
                $this->mqtt_service->publish($topic, json_encode($data[$topic] ?? []));
            } catch (\Throwable $e) {
                error_log('HardwareModel mqttReadAndPublish publish: ' . $e->getMessage());
            }
        }

        return $data;
    }
}

