<?php

namespace app;
use jamesRUS52\TinkoffInvest\TICandle;
use jamesRUS52\TinkoffInvest\TICandleIntervalEnum;
use jamesRUS52\TinkoffInvest\TIClient;
use jamesRUS52\TinkoffInvest\TICurrencyEnum;
use jamesRUS52\TinkoffInvest\TIOrderBook;
use jamesRUS52\TinkoffInvest\TISiteEnum;

require_once 'vendor/autoload.php';

class Trade
{
    public $client;
    public $history = [];
    public $token;

    public function __construct($token)
    {
        $this->token = $token;
        $this->client = new TIClient($this->token, TISiteEnum::EXCHANGE);

        $talFigi = 'BBG0016XJ8S0';
        $this->client->stopGetting();
        $this->client->subscribeGettingCandle($talFigi, TICandleIntervalEnum::MIN1);
        $this->client->subscribeGettingOrderBook($talFigi, 2);
        $this->client->startGetting(function ($obj) {
            print "action\n";
            print_r($obj);
            if ($obj instanceof TICandle)
                print 'Time: '.$obj->getTime ()->format('d.m.Y H:i:s').' Volume: '.$obj->getVolume ()."\n";
            if ($obj instanceof TIOrderBook)
                print 'Price to Buy: '.$obj->getBestPriceToBuy().' Price to Sell: '.$obj->getBestPriceToSell()."\n";
        });
    }

    public function say($msg)
    {
        echo $msg . "\r\n";
    }

    public function showBalance()
    {
        $this->say("Денех: " . $this->client
            ->getPortfolio()
            ->getCurrencyBalance(TICurrencyEnum::RUB));
    }

    public function memoryInfo()
    {
        $unit = array('b','kb','mb','gb','tb','pb');
        $size = memory_get_usage(true);
        $use = @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
        $this->say('------ ' . $use . ' ------');
    }

    public function runByAllInstruments()
    {
        $instruments = $this->client->getPortfolio()
                                    ->getAllinstruments();

        foreach ($instruments as $instrument) {
            $prev = false;
            if (isset($this->history[$instrument->getTicker()])) {
                $prev = $this->history[$instrument->getTicker()][array_key_last($this->history[$instrument->getTicker()])];
            }
            $this->history[$instrument->getTicker()][time()] = $instrument->getExpectedYieldValue();
            $say = '| ' . $instrument->getTicker() . ': ';
            $say .= $instrument->getExpectedYieldValue();

            if ($prev !== false) {
                $say .= ' | ';
                if ($instrument->getExpectedYieldValue() > $prev) {
                    $say .= '>';
                }
                if ($instrument->getExpectedYieldValue() < $prev) {
                    $say .= '<';
                }
            }

            $this->say($say);
        }

    }

}

new Trade(getenv('token'));
