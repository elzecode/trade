<?php

namespace App\Strategy\Interface;

use App\Entity\Candle;

interface StrategyByCandleInterface
{
    public function run(Candle $candle);
    public function buy(Candle $candle): bool;
    public function sale(Candle $candle): bool;
}