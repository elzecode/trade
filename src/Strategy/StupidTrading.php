<?php

namespace App\Strategy;

use App\Entity\Candle;
use App\Entity\Instrument;
use App\Enums\Operation;
use DateTime;

class StupidTrading extends StrategyByCandleBase
{
    /**
     * @var Candle $prevCandle
     */
    public $prevCandle;

    public $upCount = 0;
    public $downCount = 0;

    public Instrument $instrument;
    public float $balance;

    public function run(Candle $candle)
    {
        if (!$this->prevCandle instanceof Candle) {
            $this->printingMessage('Получаю первое сообщение с рынка');
            $this->prevCandle = $candle;
            return;
        }

        $actualPrice = $candle->getClose();
        $prevPrice = $this->prevCandle->getClose();

        // Если нет изменений в цене закрытия с предыдущей свечой то нечего не делаем
        if ($actualPrice == $prevPrice) {
            return;
        }

        $beSale = false;
        $beBuy = false;

        // Если цена последней покупики меньше текущий актуальной
        if ($this->instrument->getLastBuyPrice() < $actualPrice && $this->instrument->getLotInPortfolio() > 0) {

            // Считаем "метрики"
            $this->upCount = $actualPrice > $prevPrice ? $this->upCount + 1 : $this->upCount;
            $this->downCount = $actualPrice < $prevPrice ? $this->downCount + 1 : $this->downCount;

            // Если цена росла и начала падать
            if ($this->upCount > 0 && $this->downCount > 0) {
                $beSale = $this->sale($candle);
            }

        } else {
            $this->upCount = $this->downCount = 0;
        }

        if (!$beSale) {
            // Если нет лотов то проверяем если две подряд красных свечи то покупаем
            if ($this->getInstrument()->getLotInPortfolio() == 0 &&
                $candle->getColor() == \App\Enums\Candle::COLOR_RED &&
                $this->prevCandle->getColor() == \App\Enums\Candle::COLOR_RED &&
                $actualPrice < $this->balance)
            {
                $beBuy = $this->buy($candle);
            }
        }

        if (!$beBuy && !$beSale) {
            $this->redisMessage(
                Operation::WAIT,
                $candle->getClose(),
                $this->getBalance(),
                [
                    'lots' => $this->getInstrument()->getLotInPortfolio()
                ],
                $candle->getDateTime()->getTimestamp());
        }

        $this->printingMessage('[actual: ' . $actualPrice . ' prev: ' . $prevPrice . ']');
        $this->printingMessage('[up: ' . $this->upCount . ' down: ' . $this->downCount . ']');
        $this->printingMessage('[buyCount: ' . $this->buyCount .' saleCount: ' . $this->saleCount . ']');
        $this->printingMessage('[timeLastBuy  : ' . ($this->getInstrument()->getLastBuyDateTime() instanceOf DateTime ?
                $this->getInstrument()->getLastBuyDateTime()->format('Y-m-d H:i:s') : '-') . ']');
        $this->printingMessage('[timeLastSale : ' . ($this->getInstrument()->getLastSaleDateTime() instanceof DateTime ?
                $this->getInstrument()->getLastSaleDateTime()->format('Y-m-d H:i:s') : '-') . ']');

        $this->prevCandle = $candle;
    }

    public function buy(Candle $candle): bool
    {
        if (parent::buy($candle)) {
            $this->upCount = $this->downCount = 0;
            return true;
        }
        return false;
    }

    public function sale(Candle $candle): bool
    {
        if (parent::sale($candle)) {
            $this->upCount = $this->downCount = 0;
            return true;
        }
        return false;
    }

}