<?php

namespace App\Entity;

use DateTime;

class Client
{
    private string $url = 'https://api-invest.tinkoff.ru/openapi';
    private string $token;
    private \WebSocket\Client $wsClient;
    private bool $isSubscribe = false;

    public function __construct($token)
    {
        $this->token = $token;
        $this->wsClient = new \WebSocket\Client('wss://api-invest.tinkoff.ru/openapi/md/v1/md-openapi/ws', [
            "timeout" => 60,
            "headers" => ["authorization" => "Bearer {$this->token}"],
        ]);
    }

    public function marketOrder($figi, $type, $lots)
    {
        $response = $this->sendRequest('/orders/market-order', 'POST', ['figi' => $figi], json_encode([
            'operation' => $type,
            'lots' => $lots
        ]));

        print_r($response);
    }

    public function getHistoryCandles(DateTime $from, DateTime $to, string $figi, string $interval = '1min')
    {
        $candles = [];
        $response = $this->sendRequest('/market/candles', 'GET', [
            'figi' => $figi,
            'interval' => $interval,
            'from' => $from->format(DATE_W3C),
            'to' => $to->format(DATE_W3C)
        ]);
        foreach ($response->getData()['payload']['candles'] as $data) {
            $candles[] = $this->makeCandle($data);
        }
        return $candles;
    }

    public function candleSubscribe($callback, $figi, $interval = '1min')
    {
        $this->wsClient->send(json_encode([
            'event' => 'candle:subscribe',
            'figi' => $figi,
            'interval' => $interval
        ]));

        $this->isSubscribe = true;
        while ($this->isSubscribe) {
            if ($json = $this->wsClient->receive()) {
                $data = json_decode($json, true);
                call_user_func($callback, $this->makeCandle($data['payload']));
            }
        }
    }

    public function orderBookSubscribe($callback, $figi, $depth = 5)
    {
        $this->wsClient->send(json_encode([
            'event' => 'orderbook:subscribe',
            'figi' => $figi,
            'depth' => $depth
        ]));

        $this->isSubscribe = true;
        while ($this->isSubscribe) {
            if ($json = $this->wsClient->receive()) {
                $data = json_decode($json, true);
                call_user_func($callback, new OrderBook(
                    $data['payload']['figi'],
                    $data['payload']['bids'],
                    $data['payload']['asks']
                ));
            }

        }
    }

    public function stopSubscribe()
    {
        $this->isSubscribe = false;
    }

    public function getPortfolio() : Portfolio
    {
        return new Portfolio($this->sendRequest('/portfolio', 'GET'));
    }

    public function getPortfolioCurrencies() : Response
    {
        return $this->sendRequest('/portfolio/currencies', 'GET');
    }

    public function getNewInstrumentByTicker($ticker) : Instrument
    {
        $responseData = $this->sendRequest('/market/search/by-ticker', 'GET', ['ticker' => $ticker])
            ->getData();

        return new Instrument(
            $responseData['payload']['instruments'][0]['figi'],
            $responseData['payload']['instruments'][0]['ticker'],
            $responseData['payload']['instruments'][0]['name'],
            $responseData['payload']['instruments'][0]['currency'],
            0,
            0
        );
    }

    private function sendRequest($action, $method, $params = [], $body = null) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->url . $action);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);

        if (count($params) > 0) {
            curl_setopt(
                $curl,
                CURLOPT_URL,
                $this->url . $action . '?' . http_build_query(
                    $params
                )
            );
        }

        if ($method !== "GET") {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }

        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            [
                'Content-Type:application/json',
                'Authorization: Bearer ' . $this->token,
            ]
        );

        $out = curl_exec($curl);
        $res = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        $error = curl_error($curl);
        curl_close($curl);

        if ($res === 0) {
            throw new \Exception($error);
        }

        return new Response($out, $res);
    }

    private function makeCandle($data): Candle
    {
        return new Candle($data['figi'],
            $data['interval'],
            $data['o'],
            $data['c'],
            $data['h'],
            $data['l'],
            new DateTime($data['time'])
        );
    }

}