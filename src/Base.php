<?php


namespace App;

use App\Entity\Client;
use App\Entity\Instrument;
use App\Enums\Operation;
use Redis;

abstract class Base
{
    private string $token;
    public string $ticker;

    public Redis $redis;
    public Client $client;
    public Instrument $instrument;

    public string $tradeCurrency;
    public string $strategy;
    public float  $balance;
    public float  $startBalance;

    public function __construct($token, $ticker, $strategy)
    {
        $this->token = $token;
        $this->ticker = $ticker;
        $this->strategy = $strategy;
        $this->say('Подготовка к работе с ' . $this->ticker);
        $this->prepareRedis();
        $this->prepareClient();
        $this->prepareInstrument();
        $this->tradeCurrency = $this->instrument->getCurrency();
        $this->prepareBalance();

        $this->saveToRedis(Operation::START, 0, $this->balance);
        $this->run();
    }

    private function prepareRedis()
    {
        $this->redis = new Redis();
        $this->redis->connect('trade_redis', 6379);
        $this->redis->flushAll();
    }

    private function prepareClient()
    {
        $this->client = new Client($this->token);
    }

    private function prepareInstrument()
    {
        if ($instrument = $this->client->getPortfolio()->getInstrumentByTicker($this->ticker)) {
            $this->instrument = $instrument;
        } else {
            $this->instrument = $this->client->getNewInstrumentByTicker($this->ticker);
        }
    }

    private function prepareBalance()
    {
        $responseData = $this->client->getPortfolioCurrencies()->getData();
        $balance = 0;
        foreach ($responseData['payload']['currencies'] as $item) {
            if ($item['currency'] == $this->tradeCurrency) {
                $balance = $item['balance'];
                break;
            }
        }
        $this->balance = $this->startBalance = $balance;
    }

    public function saveToRedis($operation, $price, $balance, $additionalInfo = [], $time = false)
    {
        $this->redis->hSet($this->ticker, $time ?? time(), json_encode([
            'operation' => $operation,
            'price' => $price,
            'balance' => $balance,
            'additionalInfo' => $additionalInfo
        ]));
    }

    public function say($string)
    {
        print $string . "\n";
    }
}