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

//        $candles = $this->client->getHistoryCandles(
//            new \DateTime('2021-12-23 00:00:00', new \DateTimeZone('UTC')),
//            new \DateTime('2021-12-23 23:59:59', new \DateTimeZone('UTC')),
//            $this->instrument->getFigi()
//        );
//
//
//        foreach ($candles as $candle) {
//            $this->beforeRunStrategy($strategyInstance, $strategyInterfaces);
//            $strategyInstance->run($candle, $this->instrument, $this->balance);
//            $this->afterRunStrategy($strategyInstance, $strategyInterfaces);
//            //$this->info($candle);
//        }
//
//        die;

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

    public function beforeRunStrategy(&$strategyInstance, $strategyInterfaces)
    {
        if (in_array(BaseStrategyInterface::class, $strategyInterfaces)) {
            $strategyInstance->setInstrument($this->instrument);
            $strategyInstance->setBalance($this->balance);
        }
    }

    /**
     * @param BaseStrategyInterface $strategyInstance
     * @param $strategyInterfaces
     * @return void
     */
    public function afterRunStrategy(&$strategyInstance, $strategyInterfaces)
    {
        if (in_array(BaseStrategyInterface::class, $strategyInterfaces)) {
            $operation = $strategyInstance->getOperation();
            if ($operation != null) {
                list($type, $lots) = $operation;
                if ($type && $lots) {
                    $this->client->marketOrder($strategyInstance->getInstrument()->getFigi(), $type, $lots);
                }
            }
            $strategyInstance->cleanOperation();
            $this->instrument = $strategyInstance->getInstrument();
            $this->balance = $strategyInstance->getBalance();
            $this->strategySaid = $strategyInstance->getPrintingMessage();
            $strategyInstance->cleanPrintingMessage();
            $redisMessage = $strategyInstance->getRedisMessage();
            if ($redisMessage != null) {
                list($operation, $price, $balance, $time, $additionalInfo) = $redisMessage;
                $orderBook = $this->client->marketOrderBook($this->instrument->getFigi());
                if ($orderBook) {
                    $additionalInfo = [
                        'bids' => $orderBook->getBestBids(),
                        'asks' => $orderBook->getBestAsks()
                    ];
                }
                $this->saveToRedis($operation, $price, $balance, $additionalInfo, $time);
            }
            $strategyInstance->cleanRedisMessage();
        }
    }

    public function info($obj, $show = true)
    {
        if ($show) {
            $this->say('############################# ' . date('H:i:s'));
            $this->say('');

            $price = null;

            if ($obj instanceof Candle) {
                $price = $obj->getClose();
            } elseif ($obj instanceof OrderBook) {
                $price = $obj->getBestSalePrice();
            }


            $this->say($this->instrument->getName() . ' - ' . $price . $this->tradeCurrency . ' color: ' . (mb_strtoupper($obj->getColor())));
            $this->say('Balance: ' . $this->balance . $this->tradeCurrency . ' | Start: ' . $this->startBalance . $this->tradeCurrency);

            $this->say('Sum with lots: ' . ($this->balance + $this->instrument->getLotInPortfolio() * $price) . $this->tradeCurrency .
                                 ' | Profit: ' . (($this->balance + $this->instrument->getLotInPortfolio() * $price) - $this->startBalance) . $this->tradeCurrency);

            $this->say('Lots in balance: ' . $this->instrument->getLotInPortfolio());
            $this->say('Last buy price:  ' . $this->instrument->getLastBuyPrice());
            $this->say('Last sale price: ' . $this->instrument->getLastSalePrice());

            if ($this->strategySaid != '') {
                $this->say("\r\n" . $this->strategySaid);
            }

            $this->say('#############################');
            $this->say('');
        }
    }

}