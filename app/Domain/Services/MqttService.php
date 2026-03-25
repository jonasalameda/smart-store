<?php

declare(strict_types=1);

namespace App\Domain\Services;

use PhpMqtt\Client\MqttClient;

class MqttService
{
    private string $server = 'localhost';
    private int $port = 1883;

    /**
     * This is to publish a message to a topic.
     * Exact publish example from php-mqtt/client README.
     * @param topic the topic name to publish to
     * @param message the message to send for publication
     */
    public function publish(string $topic, string $message): void
    {
        $clientId = 'smart-store-publisher';
        $mqtt = new MqttClient($this->server, $this->port, $clientId);
        $mqtt->connect();
        $mqtt->publish($topic, $message, 0);
        $mqtt->disconnect();
    }

    /** 
     * This is to subscribe to a topic and handle incoming messages.
     * There are subscribe examples from in the php-mqtt/client README, view the source in the end of this file as a comment
     * @param topic the topic name to subscribe to
     * @param callable $callback function($topic, $message, $retained, $matchedWildcards)
     */
    public function subscribe(string $topic, callable $callback): void
    {
        $clientId = 'smart-store-subscriber';

        $mqtt = new MqttClient($this->server, $this->port, $clientId);
        $mqtt->connect();
        $mqtt->subscribe($topic, $callback, 0);
        $mqtt->loop(true);
        $mqtt->disconnect();
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