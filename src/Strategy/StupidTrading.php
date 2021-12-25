<?php

namespace App\Strategy;

use App\Entity\Candle;
use App\Entity\Instrument;

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
        // todo: После продажи сделать новую покупку с учетом цены продажи


        if (!$this->prevCandle instanceof Candle) {
            $this->say('Получаю первое сообщение с рынка');
            $this->prevCandle = $candle;
            return;
        }

        $actualPrice = $candle->getClose();
        $prevPrice = $this->prevCandle->getClose();

        // Первый вход, если нет лотов, нет последний цены продажи
        if ($this->getInstrument()->getLastBuyPrice() == 0 &&
            $this->getInstrument()->getLastSalePrice() == 0 &&
            $actualPrice < $this->getBalance())
        {
            $this->buy($candle);
            return;
        }

        // Если цена последней покупики меньше текущий актуальной
        if ($this->instrument->getLastBuyPrice() < $actualPrice) {

            // Считаем "метрики"
            $this->upCount = $actualPrice > $prevPrice ? $this->upCount + 1 : $this->upCount;
            $this->downCount = $actualPrice < $prevPrice ? $this->downCount + 1 : $this->downCount;

            // Если цена росла и начала падать
            if ($this->upCount > 0 && $this->downCount > 0) {
                $this->sale($candle);
            }

        } else {
            $this->upCount = $this->downCount = 0;
        }

        // Если лотов 0 и цена последней продажи выше текущий то покупаем
        if ($this->getInstrument()->getLotInPortfolio() == 0 &&
            $this->getInstrument()->getLastSalePrice() > $actualPrice &&
            $actualPrice < $this->balance)
        {
            $this->buy($candle);
        }

        print '[actual: ' . $actualPrice . ' prev: ' . $prevPrice . ']';
        print '[up: ' . $this->upCount . ' down: ' . $this->downCount . ']';

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