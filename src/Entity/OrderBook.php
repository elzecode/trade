<?php

namespace App\Entity;

use DateTime;
use DateTimeZone;

class OrderBook
{
    private DateTime $time;
    private string $figi;
    private array $bids;
    private array $asks;

    public function __construct(
        $figi,
        $bids = [],
        $asks = []
    )
    {
        $this->time = new DateTime();
        $this->figi = $figi;
        $this->bids = $bids;
        $this->asks = $asks;
    }

    public function getTime()
    {
        return $this->time;
    }

    public function getFigi()
    {
        return $this->figi;
    }

    public function getBids()
    {
        return $this->bids;
    }

    public function getAsks()
    {
        return $this->asks;
    }

    public function getBestBuyPrice()
    {
        return $this->getBids()[0][0];
    }

    public function getBestSalePrice()
    {
        return $this->getAsks()[0][0];
    }
}
