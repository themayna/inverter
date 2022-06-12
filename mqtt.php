<?php

require_once('vendor/autoload.php');

class Mqtt
{
    protected $mqtt;
    protected $client;
    const HOST = 'petuniilor.go.ro';
    protected $status;

    public function __construct()
    {
        $this->mqtt = new \PhpMqtt\Client\MqttClient(host: self::HOST);
        $this->client = new GuzzleHttp\Client();
        $response = $this->client->request(
            method: 'POST',
            uri: self::HOST . ':35001/zeroconf/info',
            options: [
                'body' => json_encode(['deviceid', 'data' => []])
            ]
        );
        $info = json_decode($response->getBody()->getContents(),true);

        $this->status = $info['data']['switch'] = 'on' ? true : false;

        $this->mqtt->connect();
    }

    public function subscribe()
    {
        $this->mqtt->subscribe('solar_assistant/total/battery_state_of_charge/state', function ($topic, $percent) {
            echo sprintf(
                "Battery percentage is at %s and switch is tunned %s\n",
                $percent,
                $this->status == true ? "On" : "Off"
            );
            if ($percent == 32 && $this->status == false) {
                //start la retea
                $this->client->request(
                    method: 'POST',
                    uri: self::HOST . ':35001/zeroconf/switch',
                    options: [
                        'body' => json_encode(['data' => ['switch' => 'on']])
                    ]
                );
                $this->status = true;
                echo "Turned the grid ON\n";
            }
            if ($percent == 42 && $this->status == true) {
                //stop la retea
                $this->client->request(
                    method: 'POST',
                    uri: self::HOST . ':35001/zeroconf/switch',
                    options: [
                        'body' => json_encode(['data' => ['switch' => 'off']])
                    ]
                );
                $this->status = false;
                echo "Turned the grid OFF\n";
            }
        }, 0);
        $this->mqtt->loop();
        $this->mqtt->disconnect();
    }
}

(new Mqtt())->subscribe();
