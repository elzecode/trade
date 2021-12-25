<?php

namespace App\Strategy\Interface;

use App\Entity\Instrument;

interface BaseStrategyInterface
{
    public function setInstrument(Instrument $instrument);
    public function getInstrument(): Instrument;
    public function setBalance(float $balance);
    public function getBalance(): float;
    public function cleanSay(): bool;
    public function say(string $msg);
    public function whatYouSaid(): string;
}