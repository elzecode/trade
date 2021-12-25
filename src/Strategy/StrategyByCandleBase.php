<?php

namespace App\Strategy;

use App\Entity\Candle;
use App\Entity\Instrument;
use App\Strategy\Interface\BaseStrategyInterface;
use App\Strategy\Interface\StrategyByCandleInterface;

abstract class StrategyByCandleBase implements BaseStrategyInterface, StrategyByCandleInterface
{
    public Instrument $instrument;
    public float $balance;

    public $message;

    public function setInstrument(Instrument $instrument)
    {
        $this->instrument = $instrument;
    }
    public function getInstrument(): Instrument
    {
        return $this->instrument;
    }
    public function setBalance(float $balance)
    {
        $this->balance = $balance;
    }
    public function getBalance(): float
    {
        return $this->balance;
    }
    public function buy(Candle $candle): bool
    {
        $this->setBalance($this->getBalance() - $candle->getClose());
        $this->getInstrument()->setLastBuyPrice($candle->getClose());
        $this->getInstrument()->setLotInPortfolio($this->getInstrument()->getLotInPortfolio() + 1);
        return true;
    }
    public function sale(Candle $candle): bool
    {
        $salePrice = ($candle->getClose() * $this->instrument->getLotInPortfolio());
        $this->setBalance($this->getBalance() + $salePrice);
        $this->getInstrument()->setLotInPortfolio(0);
        $this->getInstrument()->setLastSalePrice($salePrice);
        $this->getInstrument()->setLastBuyPrice(0);
        return true;
    }
    public function say(string $msg)
    {
        $this->message .= $msg . "\r\n";
    }
    public function whatYouSaid(): string
    {
        return $this->message;
    }
    public function cleanSay(): bool
    {
        $this->message = '';
        return true;
    }
}