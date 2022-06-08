<?php

require_once('vendor/autoload.php');

class Mqtt
{
    protected $mqtt;
    protected $client;
    const HOST = 'petuniilor.go.ro';

    public function __construct()
    {
        $this->mqtt = new \PhpMqtt\Client\MqttClient(host: self::HOST);
        $this->client = new GuzzleHttp\Client();
        $this->mqtt->connect();
    }

    public function subscribe()
    {
        $this->mqtt->subscribe('solar_assistant/total/battery_state_of_charge/state', function ($topic, $message) {

            echo sprintf("Received message on topic [%s]: %s\n", $topic, $message);
            if ($message == 75) {
                //start la retea
                $this->client->request(
                    method: 'POST',
                    uri: self::HOST . ':35001/zeroconf/switch',
                    options: [
                        'body' => json_encode(['data' => ['switch' => 'on']])
                    ]
                );
            }
//            if ($message == 75) {
//                //stop la retea
//                $this->client->request(
//                    method: 'POST',
//                    uri: self::HOST . ':35001/zeroconf/switch',
//                    options: [
//                        'body' => json_encode(['data' => ['switch' => 'off']])
//                    ]
//                );
//            }

        }, 0);
        $this->mqtt->loop();
        $this->mqtt->disconnect();
    }
}

(new Mqtt())->subscribe();
