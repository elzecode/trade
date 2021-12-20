<?php

namespace app;

use Dotenv\Dotenv;
use jamesRUS52\TinkoffInvest\TICandle;
use jamesRUS52\TinkoffInvest\TICandleIntervalEnum;
use jamesRUS52\TinkoffInvest\TIClient;
use jamesRUS52\TinkoffInvest\TICurrencyEnum;
use jamesRUS52\TinkoffInvest\TIException;
use jamesRUS52\TinkoffInvest\TIInstrument;
use jamesRUS52\TinkoffInvest\TIOrderBook;
use jamesRUS52\TinkoffInvest\TISiteEnum;
use WebSocket\Client;
use WebSocket\ConnectionException;

require_once 'vendor/autoload.php';

$dotenv = Dotenv::createUnsafeImmutable(__DIR__);
$dotenv->load();

class Bot
{
    private string $token;

    private TIClient $client;
    private TIInstrument $instrument;
    private $ticker;
    private $history = [];

    private $bank = 0;
    private $bankStart = 0;
    private $lastOperation = [
        'closePrice' => 0,
        'lotCount' => 0,
        'lastSalePrice' => 0
    ];
    private $upCountTmp = 0;
    private $downCountTmp = 0;

    private $buyCount = 0;

    public function __construct($token, $ticker)
    {
        $this->token = $token;
        $this->ticker = $ticker;
        $this->buildClient();
        $this->run();
    }

    private function buildClient()
    {
        $this->client = new TIClient($this->token, TISiteEnum::EXCHANGE);
    }

    private function run()
    {
        try {
            $this->instrument = $this->client->getInstrumentByTicker($this->ticker);
            $portfolio = $this->client->getPortfolio();
        } catch (TIException $exception) {
            echo $exception->getMessage();
            die();
        }

        $this->bankStart = $this->bank = $portfolio->getCurrencyBalance(TICurrencyEnum::RUB);
        $this->client->subscribeGettingCandle($this->instrument->getFigi(), TICandleIntervalEnum::MIN1);

        while (true) {
            try {
                $this->client->startGetting(function (TICandle $candle) {
                    //echo chr(27).chr(91).'H'.chr(27).chr(91).'J';
                    $this->analizator($candle, $this->instrument);
                }, null, null);
            } catch (ConnectionException $e) {
            }

        }
    }

    public function analizator(TICandle $candle)
    {
        /**
         * @var TICandle $prevCandle
         */
        $prevCandle = count($this->history) > 0 ? $this->history[count($this->history) - 1] : false;

        $priceBuy = $this->instrument->getLot() * $candle->getClose();
        $prevPriceBuy = $prevCandle ? $this->instrument->getLot() * $prevCandle->getClose() : $priceBuy;


        $this->say('Время: ' . $candle->getTime()->setTimezone(new \DateTimeZone('Europe/Moscow'))->format('d.m.Y H:i:s'));
        $this->say('Цена: ' . $priceBuy . ' ' . (
                $prevPriceBuy < $priceBuy ? '- Растет' :
                    ($prevPriceBuy > $priceBuy ? '- Падает' : '')
            ));
        $this->say('Цена закрытия: ' . $candle->getClose());

        $metricsUse = true;
        if ($this->lastOperation['lotCount'] !== 0) {
            $this->say('| Думаю о продаже');
            if ($this->downCountTmp > 29) {
                $this->say('! Продаю в минус, долгий простой');
                $this->sale($candle);
            }
            if ($candle->getClose() > $this->lastOperation['closePrice'] && $this->downCountTmp > 0) {
                $metricsUse = !$this->sale($candle);
            }
            if ($candle->getClose() < $this->lastOperation['closePrice']) {
                $this->buy($candle);
            }
        } else {
            $this->say('| Думаю о покупке');
            if ($this->lastOperation['lastSalePrice'] > $priceBuy || $this->buyCount == 0) {
                $metricsUse = !$this->buy($candle);
            }
        }

        if ($metricsUse) {
            if ($this->lastOperation['closePrice'] == $candle->getClose()) {
                $this->say('! Цена закрытия равна цене закрытия последний операции, сбрасываю метрики');
                $this->upCountTmp = 0;
                $this->downCountTmp = 0;
            }

            $operand = $prevCandle->getClose();

            $this->upCountTmp = $candle->getClose() > $operand ?
                $this->upCountTmp + 1 :
                $this->upCountTmp;

            $this->downCountTmp = $candle->getClose() < $operand ?
                $this->downCountTmp + 1 :
                $this->downCountTmp;
        }


        $this->say('Баланс: ' . $this->bank . '(' . (($this->lastOperation['lotCount'] * $candle->getClose()) + $this->bank) . ') Изначально ' . $this->bankStart);
        if ($metricsUse) {
            $this->say('up: ' . $this->upCountTmp . ' ' . 'down: ' . $this->downCountTmp . ' | operand : ' . $operand);
        }
        $this->say('lastOperation: closePrice - ' . $this->lastOperation['closePrice'] . '(' . $this->lastOperation['lotCount'] . ') sum - ' . ($this->lastOperation['lotCount'] * $candle->getClose()) . ' lastSalePrice - ' . $this->lastOperation['lastSalePrice']);
        $this->say('----------------------------------------------------------');
        $this->history = [$candle];
    }

    private function buy(TICandle $candle)
    {
        $priceBuy = $this->instrument->getLot() * $candle->getClose();
        if ($this->bank > $priceBuy) {
            $this->buyCount++;
            $this->bank = $this->bank - $priceBuy;
            $this->lastOperation['closePrice'] = $candle->getClose();
            $this->lastOperation['lotCount'] += $this->instrument->getLot();

            $this->say('! Купил ' . $this->instrument->getLot() . ' лот(ов) на сумму ' . $priceBuy);
            $this->downCountTmp = $this->upCountTmp = 0;
            return true;
        }
        return false;
    }

    private function sale(TICandle $candle)
    {
        $lots = $this->lastOperation['lotCount'];
        $priceSale = $lots * $candle->getClose();
        $this->bank = $this->bank + $priceSale;
        $this->lastOperation['lotCount'] = 0;
        $this->lastOperation['closePrice'] = 0;
        $this->lastOperation['lastSalePrice'] = $priceSale;

        $this->say('! Продал ' . $lots . ' лот(ов) на сумму ' . $priceSale);
        $this->downCountTmp = $this->upCountTmp = 0;
        return true;
    }

    private function say($string)
    {
        print $string . "\n";
    }
}

new Bot(getenv('TOKEN'), 'AFLT');