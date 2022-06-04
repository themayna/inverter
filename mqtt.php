<?php

require_once('vendor/autoload.php');

class Mqtt
{

    protected $mqtt;

    public function __construct()
    {
        $this->mqtt = new \PhpMqtt\Client\MqttClient(host: 'solar-assistant.local');
        echo "created";
        $this->mqtt->connect();
    }

    public function subscribe()
    {
        echo "inside the subscribe function";
        $this->mqtt->subscribe('#', function ($topic, $message) {
            echo sprintf("Received message on topic [%s]: %s\n", $topic, $message);
        }, 0);
        $this->mqtt->loop(true);
        $this->mqtt->disconnect();
    }
}

(new Mqtt())->subscribe();
