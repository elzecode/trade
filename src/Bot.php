<?php

namespace App;

use App\Entity\OrderBook;

class Bot extends Base
{
    /**
     * @var OrderBook $prevOrderBook
     */
    public $prevOrderBook;

    public $tmpSaleUp = 0;
    public $tmpSaleDown = 0;
    public $tmpSaleStatic = 0;

    public function run()
    {
        $this->client->orderBookSubscribe(function ($data) {
            $this->analizator($data);
        }, $this->instrument->getFigi());
    }

    public function analizator(OrderBook $orderBook)
    {
        if ($this->prevOrderBook instanceof OrderBook) {
            $prevBuyPrice = $this->prevOrderBook->getBestBuyPrice();
            $prevSalePrice = $this->prevOrderBook->getBestSalePrice();
        } else {
            $this->prevOrderBook = $orderBook;
            return;
        }

        $this->calcTmp($orderBook);

        $this->say('------------------------------------------------------------------------------');
        $this->say($orderBook->getTime()->format('H:i:s'));
        $this->printInfo($orderBook);

        if ($this->instrument->getLotInPortfolio() == 0) {
            if ($this->instrument->getLastBuyPrice() == 0) {
                $this->buy($orderBook);
            } else {
                if ($this->instrument->getLastSalePrice() > ($orderBook->getBestBuyPrice() + 0.01)) {
                    $this->buy($orderBook);
                }
            }
        } else {
            if ($this->tmpSaleUp > 0 && $this->tmpSaleDown > 0) {
                if (($orderBook->getBestSalePrice() + 0.01) > $this->instrument->getLastBuyPrice()) {
                    $this->sale($orderBook);
                }
            }
        }

        $this->say("\r\n");

        $this->prevOrderBook = $orderBook;
        $this->say('------------------------------------------------------------------------------');
    }

    public function sale(OrderBook $orderBook)
    {
        $salePrice = $orderBook->getBestSalePrice() - 0.01;
        $this->balance = $this->balance + $salePrice;
        $this->instrument->setLotInPortfolio($this->instrument->getLotInPortfolio() - 1);
        $this->instrument->setLastSalePrice($salePrice);
        $this->tmpSaleUp = 0;
        $this->tmpSaleDown = 0;
        $this->tmpSaleStatic = 0;

        $this->say('Продал за ' . $salePrice);
    }

    public function buy(OrderBook $orderBook)
    {
        $buyPrice = $orderBook->getBestBuyPrice() + 0.01;
        if ($this->balance > $buyPrice) {
            $this->balance = $this->balance - $buyPrice;
            $this->instrument->setLotInPortfolio($this->instrument->getLotInPortfolio() + 1);
            $this->instrument->setLastBuyPrice($buyPrice);
            $this->tmpSaleUp = 0;
            $this->tmpSaleDown = 0;
            $this->tmpSaleStatic = 0;
            $this->say('Купил за ' . $buyPrice);
        }
    }

    public function calcTmp(OrderBook $orderBook)
    {
        if ($orderBook->getBestSalePrice() == ($this->instrument->getLastBuyPrice() - 0.01)) {
            $this->tmpSaleUp = 0;
            $this->tmpSaleDown = 0;
            $this->tmpSaleStatic = 0;
        } else {
            $this->tmpSaleUp = $orderBook->getBestSalePrice() > $this->prevOrderBook->getBestSalePrice() ?
                ++$this->tmpSaleUp : $this->tmpSaleUp;

            $this->tmpSaleDown = $orderBook->getBestSalePrice() < $this->prevOrderBook->getBestSalePrice() ?
                ++$this->tmpSaleDown : $this->tmpSaleDown;

            $this->tmpSaleStatic = $orderBook->getBestSalePrice() == $this->prevOrderBook->getBestSalePrice() ?
                ++$this->tmpSaleStatic : 0;
        }
    }

    public function printInfo(OrderBook $orderBook)
    {
        $prevBuyPrice = $this->prevOrderBook->getBestBuyPrice();
        $prevSalePrice = $this->prevOrderBook->getBestSalePrice();

        $buyPrice = $orderBook->getBestBuyPrice();
        $salePrice = $orderBook->getBestSalePrice();

        $this->say('B: ' . $buyPrice . ($buyPrice > $prevBuyPrice ?
                ' >' : ($buyPrice < $prevBuyPrice ? ' <' : '')) .
                ' S: ' . $salePrice . ($salePrice > $prevSalePrice ?
                ' >' : ($salePrice < $prevSalePrice ? ' <' : '')) .
                ' (up: ' . $this->tmpSaleUp . ' down: ' . $this->tmpSaleDown . ' static: ' . $this->tmpSaleStatic . ')');
        $this->say('Куплено: ' . $this->instrument->getLotInPortfolio() .
            ' На сумму - ' . $this->instrument->getLotInPortfolio() * $salePrice .
            ' Цена покупки - ' . $this->instrument->getLastBuyPrice());

        $this->say('Банк: ' . $this->balance
            . '(' . ($this->balance + ($this->instrument->getLotInPortfolio() * $salePrice)) . ')'
            . ' | Изначально ' . $this->startBalance);
        $this->say("\r\n");


    }

}