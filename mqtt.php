<?php

require_once('vendor/autoload.php');

class Mqtt
{

    protected $mqtt;

    public function __construct()
    {
        $this->mqtt = new \PhpMqtt\Client\MqttClient(host: 'petuniilor.go.ro');
        $this->mqtt->connect();
    }

    public function subscribe()
    {
        $this->mqtt->subscribe('#', function ($topic, $message) {
            echo sprintf("Received message on topic [%s]: %s\n", $topic, $message);
        }, 0);
        $this->mqtt->loop(true);
        $this->mqtt->disconnect();
    }
}

(new Mqtt())->subscribe();
