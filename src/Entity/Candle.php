<?php

namespace App\Entity;

use DateTime;

class Candle
{
    private DateTime $time;
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
        $low
    )
    {
        $this->time = new DateTime();
        $this->figi = $figi;
        $this->interval = $interval;
        $this->open = $open;
        $this->close = $close;
        $this->high = $high;
        $this->low = $low;
    }

    public function getTime()
    {
        return $this->time;
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
}