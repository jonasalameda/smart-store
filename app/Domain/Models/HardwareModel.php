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
        // $output = shell_exec("python3 " . APP_BASE_DIR_PATH . "/public/assets/python/TemperatureHumidityReader.py") ?? '{"temperature":0,"humidity":0}';
        // $output = shell_exec("./" . APP_BASE_DIR_PATH . "/public/assets/arduino/TemperatureHumidityReader.py") ?? '{"temperature":0,"humidity":0}';

        $output = shell_exec("python3 " . APP_BASE_DIR_PATH . "/public/assets/python/read_serial.py") ?? '{"Frig1":{"temperature":0,"humidity":0},"Frig2":{"temperature":0,"humidity":0}}';

        $defaultData = [
            'Frig1' => ['temperature' => null, 'humidity' => null],
            'Frig2' => ['temperature' => null, 'humidity' => null]
        ];

        $data = json_decode($output, true) ?? $defaultData;

        // Decode the JSON output from the Python script into an associative array for easier access. I used json format in the Python to make sure it works.
        // $data = json_decode($output, true) ?? ['temperature' => null, 'humidity' => null];
        // $data = json_decode($output, true) ?? ['temperature' => null, 'humidity' => null];
        
        // $this->mqtt_service->publish($topic, json_encode($data));
        $this->mqtt_service->publish('Frig1', json_encode($data['Frig1']));
        $this->mqtt_service->publish('Frig2', json_encode($data['Frig2']));

        return $data;
    }
}

