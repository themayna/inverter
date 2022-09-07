<?php

require_once('vendor/autoload.php');

class Mqtt
{
    protected $mqtt;
    protected $client;
    const SOLAR_HOST = '192.168.100.108';
    const SONOFF_HOST = '192.168.100.130:8081';
    protected $switch;
    protected $state;

    public function __construct()
    {
        $this->initConnection()->subscribe();
    }

    protected function initConnection()
    {
        try {
            $this->mqtt = new \PhpMqtt\Client\MqttClient(host: self::SOLAR_HOST);
            $this->client = new GuzzleHttp\Client();
            $response = $this->client->request(
                method: 'POST',
                uri: self::SONOFF_HOST . '/zeroconf/info',
                options: [
                    'body' => json_encode(['deviceid', 'data' => []])
                ]
            );
            $info = json_decode($response->getBody()->getContents(), true);

            $this->switch = $info['data']['switch'] == 'on' ? true : false;

            $this->mqtt->connect();

            return $this;
        } catch (Throwable $exception) {
            echo sprintf($exception->getMessage());
            $this->initConnection()->subscribe();
        }
    }

    public function subscribe()
    {
        try {
            $this->mqtt->subscribe('solar_assistant/inverter_1/device_mode/state', function ($topic, $message) {
                $this->state = $message;
            }, 0);

            $this->mqtt->subscribe('solar_assistant/total/battery_state_of_charge/state', function ($topic, $percent) {
                echo sprintf(
                    "Battery percentage is at %s and switch is tunned %s\n",
                    $percent,
                    $this->switch == true ? "On" : "Off"
                );
                if ($percent <= 35 && $this->switch == false) {
//                  start la retea
                    $this->client->request(
                        method: 'POST',
                        uri: self::SONOFF_HOST . '/zeroconf/switch',
                        options: [
                            'body' => json_encode(['data' => ['switch' => 'on']])
                        ]
                    );
                    $this->switch = true;
                    echo "Turned the grid ON\n";
                }
                if ($percent >= 40 && $this->switch == true && $this->state == 'Solar/Battery') {
//                    stop la retea
                    $this->client->request(
                        method: 'POST',
                        uri: self::SONOFF_HOST . '/zeroconf/switch',
                        options: [
                            'body' => json_encode(['data' => ['switch' => 'off']])
                        ]
                    );
                    $this->switch = false;
                    echo "Turned the grid OFF\n";
                }
            }, 0);
            $this->mqtt->loop();
            $this->mqtt->disconnect();
        } catch (Throwable $exception) {
            echo sprintf($exception->getMessage());
            $this->initConnection()->subscribe();
        }
    }
}

(new Mqtt());
