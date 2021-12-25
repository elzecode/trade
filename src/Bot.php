<?php

namespace App;

use App\Entity\Candle;
use App\Entity\OrderBook;
use App\Strategy\Interface\BaseStrategyInterface;
use App\Strategy\Interface\StrategyByCandleInterface;
use ReflectionClass;

class Bot extends Base
{
    public string $strategySaid = '';

    public function run()
    {
        $this->say('Подготовка стратегии');
        $this->say('');

        try {
            $reflection = new ReflectionClass('App\\Strategy\\' . $this->strategy);
            $strategyInterfaces = $reflection->getInterfaceNames();
            $strategyInstance = $reflection->newInstance();
        } catch (\Exception $exception) {
            $this->say('Стратегия ' . $this->strategy . ' не найдена');
            die;
        }

        if (in_array(StrategyByCandleInterface::class, $strategyInterfaces)) {
            $this->client->candleSubscribe(function (Candle $candle) use ($strategyInstance, $strategyInterfaces) {
                $this->beforeRunStrategy($strategyInstance, $strategyInterfaces);
                $strategyInstance->run($candle, $this->instrument, $this->balance);
                $this->afterRunStrategy($strategyInstance, $strategyInterfaces);
                $this->info($candle);
            }, $this->instrument->getFigi());
        }

//        if (in_array(StrategyByOrderBookInterface::class, $strategyInterfaces)) {
//            $this->client->orderBookSubscribe(function (OrderBook $orderBook) use ($strategyInstance) {
//                $strategyInstance->run($orderBook);
//                $this->info($orderBook);
//            }, $this->instrument->getFigi());
//        }
    }

    public function syncResultStrategy(BaseStrategyInterface $strategyInstance)
    {
        $this->instrument = $strategyInstance->getInstrument();
        $this->balance = $strategyInstance->getBalance();
    }

    public function beforeRunStrategy(&$strategyInstance, $strategyInterfaces)
    {
        if (in_array(BaseStrategyInterface::class, $strategyInterfaces)) {
            $strategyInstance->setInstrument($this->instrument);
            $strategyInstance->setBalance($this->balance);
        }
    }

    public function afterRunStrategy(&$strategyInstance, $strategyInterfaces)
    {
        if (in_array(BaseStrategyInterface::class, $strategyInterfaces)) {
            $this->instrument = $strategyInstance->getInstrument();
            $this->balance = $strategyInstance->getBalance();
            $this->strategySaid = $strategyInstance->whatYouSaid();
            $strategyInstance->cleanSay();
        }
    }

    public function info($obj)
    {
        $this->say('############################# ' . date('H:i:s'));

        $price = null;

        if ($obj instanceof Candle) {
            $price = $obj->getClose();
        } elseif ($obj instanceof OrderBook) {
            $price = $obj->getBestSalePrice();
        }


        $this->say($this->instrument->getName() . ' - ' . $price . $this->tradeCurrency);
        $this->say('Balance: ' . $this->balance . $this->tradeCurrency . ' | Start: ' . $this->startBalance . $this->tradeCurrency);
        $this->say('Lots in balance: ' . $this->instrument->getLotInPortfolio());
        $this->say('Last buy price:  ' . $this->instrument->getLastBuyPrice());
        $this->say('Last sale price: ' . $this->instrument->getLastSalePrice());

        if ($this->strategySaid != '') {
            $this->say('');
            $this->say($this->strategySaid);
        }

        $this->say('#############################');
        $this->say('');
    }

}