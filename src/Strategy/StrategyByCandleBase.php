<?php

namespace App\Strategy;

use App\Entity\Candle;
use App\Entity\Instrument;
use App\Enums\Operation;
use App\Strategy\Interface\BaseStrategyInterface;
use App\Strategy\Interface\StrategyByCandleInterface;

abstract class StrategyByCandleBase implements BaseStrategyInterface, StrategyByCandleInterface
{
    public Instrument $instrument;
    public float $balance;

    public $printingMessage;
    public $redisMessage;
    public $operation;

    public $buyCount = 0;
    public $saleCount = 0;

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
        $priceWithTax = $this->round($candle->getClose() / getenv('TAX'));
        $countLotBeCanBuy = floor($this->getBalance() / $priceWithTax);
        $this->setBalance($this->round($this->getBalance() - ($countLotBeCanBuy * $priceWithTax)));
        $this->getInstrument()->setLastBuyPrice($priceWithTax);
        $this->getInstrument()->setLotInPortfolio($this->getInstrument()->getLotInPortfolio() + $countLotBeCanBuy);
        $this->getInstrument()->setLastBuyDateTime($candle->getDateTime());
        $this->buyCount++;

        if (getenv('TRADING')) {
            $this->operation(Operation::BUY, $countLotBeCanBuy);
        }

        $this->redisMessage(
            Operation::BUY,
            $candle->getClose(),
            $this->getBalance(),
            [
                'lots' => $this->instrument->getLotInPortfolio()
            ],
            $candle->getDateTime()->getTimestamp()
        );
        return true;
    }
    public function sale(Candle $candle): bool
    {
        $priceWithTax = $this->round($candle->getClose() / getenv('TAX'));
        $saleLots = $this->instrument->getLotInPortfolio();
        $salePrice = $this->round($priceWithTax * $saleLots);
        $this->setBalance($this->round($this->getBalance() + $salePrice));
        $this->getInstrument()->setLotInPortfolio(0);
        $this->getInstrument()->setLastSalePrice($priceWithTax);
        $this->getInstrument()->setLastBuyPrice(0);
        $this->getInstrument()->setLastSaleDateTime($candle->getDateTime());
        $this->saleCount++;

        if (getenv('TRADING')) {
            $this->operation(Operation::SALE, $saleLots);
        }

        $this->redisMessage(
            Operation::SALE,
            $candle->getClose(),
            $this->getBalance(),
            [
                'lots' => $this->instrument->getLotInPortfolio()
            ],
            $candle->getDateTime()->getTimestamp()
        );
        return true;
    }
    public function printingMessage(string $msg)
    {
        $this->printingMessage .= $msg . "\r\n";
    }
    public function getPrintingMessage(): string
    {
        return $this->printingMessage;
    }
    public function cleanPrintingMessage(): bool
    {
        $this->printingMessage = '';
        return true;
    }
    public function redisMessage($operation, $price, $balance, $additionalInfo = [], $time = false): bool
    {
        $this->redisMessage = [$operation, $price, $balance, $time, $additionalInfo];
        return true;
    }
    public function getRedisMessage(): array | null
    {
        return $this->redisMessage;
    }
    public function cleanRedisMessage(): bool
    {
        $this->redisMessage = null;
        return true;
    }
    public function operation($type, $lots): bool
    {
        $this->operation = [$type, $lots];
        return true;
    }
    public function getOperation(): array|null
    {
        return $this->operation;
    }
    public function cleanOperation(): bool
    {
        $this->operation = null;
        return true;
    }

    private function round($value)
    {
        return bcdiv($value, 1, 2);
    }
}