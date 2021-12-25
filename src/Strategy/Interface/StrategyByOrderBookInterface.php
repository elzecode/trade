<?php

namespace App\Strategy\Interface;

use App\Entity\OrderBook;

interface StrategyByOrderBookInterface
{
    public function run(OrderBook $orderBook);
    public function buy(OrderBook $orderBook): bool;
    public function sale(OrderBook $orderBook): bool;
}