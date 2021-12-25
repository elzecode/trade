<?php

namespace App\Strategy;

use App\Strategy\Interface\StrategyByOrderBookInterface;

class OrderBook implements StrategyByOrderBookInterface
{
    public function run(\App\Entity\OrderBook $orderBook)
    {
        var_dump($orderBook);
    }
}