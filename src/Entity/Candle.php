<?php

namespace App\Entity;

use DateTime;

class Candle
{
    private DateTime $dateTime;
    private string $figi;
    private string $interval;
    private float $open;
    private float $close;
    private float $high;
    private float $low;

    public function __construct(
        $figi,
        $interval,
        $open,
        $close,
        $high,
        $low,
        $time
    )
    {
        $this->dateTime = $time;
        $this->figi = $figi;
        $this->interval = $interval;
        $this->open = $open;
        $this->close = $close;
        $this->high = $high;
        $this->low = $low;
    }

    public function getDateTime()
    {
        return $this->dateTime;
    }

    public function getFigi()
    {
        return $this->figi;
    }

    public function getInterval()
    {
        return $this->interval;
    }

    public function getOpen()
    {
        return $this->open;
    }

    public function getClose()
    {
        return $this->close;
    }

    public function getHigh()
    {
        return $this->high;
    }

    public function getLow()
    {
        return $this->low;
    }

    public function getColor()
    {
        return $this->getOpen() > $this->getClose() ?
            \App\Enums\Candle::COLOR_RED :
            \App\Enums\Candle::COLOR_GREEN;
    }
}