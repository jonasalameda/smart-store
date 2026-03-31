<?php

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
     * This method is to read the temperature and humidity data from the DHT11 sensor using a Python script, and then publish the data to a specified MQTT topic using the MqttService.
     */
    public function mqttReadAndPublish(): array
    {
        $cmd = "python3 " . APP_BASE_DIR_PATH . "/public/assets/python/read_serial.py 2>&1";
        $output = shell_exec($cmd);

        if (empty($output)) {
            $output = '{"Frig1":{"temperature":25,"humidity":60},"Frig2":{"temperature":22,"humidity":55}}';
            file_put_contents($logFile, "Using default data\n\n", FILE_APPEND);
        }

        $defaultData = [
            'Frig1' => ['temperature' => null, 'humidity' => null],
            'Frig2' => ['temperature' => null, 'humidity' => null]
        ];

        $data = json_decode($output, true) ?? $defaultData;

        $this->mqtt_service->publish('Frig1', json_encode($data['Frig1']));
        $this->mqtt_service->publish('Frig2', json_encode($data['Frig2']));

        return $data;
    }
}

