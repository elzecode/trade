<?php

namespace App\Entity;

use DateTime;

class Instrument
{
    private $figi;
    private $ticker;
    private $name;
    private $currency;
    private $lotInPortfolio;
    private $lastBuyPrice = 0;
    private $lastSalePrice = 0;
    private DateTime $lastSaleDataTime;

    public function __construct(
        $figi,
        $ticker,
        $name,
        $currency,
        $lotInPortfolio,
        $lastBuyPrice
    )
    {
        $this->figi = $figi;
        $this->ticker = $ticker;
        $this->name = $name;
        $this->currency = $currency;
        $this->lotInPortfolio = $lotInPortfolio;
        $this->lastBuyPrice = $lastBuyPrice;
        $this->lastSaleDataTime = new DateTime();
    }

    public function getLastSaleDataTime()
    {
        return $this->lastSaleDataTime;
    }

    public function getLastBuyPrice()
    {
        return $this->lastBuyPrice;
    }

    public function getLastSalePrice()
    {
        return $this->lastSalePrice;
    }

    public function setLastBuyPrice($value)
    {
        return $this->lastBuyPrice = $value;
    }

    public function setLastSalePrice($value)
    {
        return $this->lastSalePrice = $value;
    }

    public function getFigi()
    {
        return $this->figi;
    }

    public function getTicker()
    {
        return $this->ticker;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function getLotInPortfolio()
    {
        return $this->lotInPortfolio;
    }

    public function setLotInPortfolio($value)
    {
        $this->lotInPortfolio = $value;
    }
}