<?php


namespace App;

use App\Entity\Client;
use App\Entity\Instrument;

abstract class Base
{
    private string $token;
    public string $ticker;

    public Client $client;
    public Instrument $instrument;

    public $tradeCurrency;
    public $balance;
    public $startBalance;

    public function __construct($token, $ticker)
    {
        $this->token = $token;
        $this->ticker = $ticker;
        $this->say('Подготовка к работе с ' . $this->ticker);
        $this->buildClient();
        $this->prepareInstrument();
        $this->tradeCurrency = $this->instrument->getCurrency();
        $this->prepareBalance();

        $this->run();
    }

    private function buildClient()
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

    public function say($string)
    {
        print $string . "\n";
    }
}