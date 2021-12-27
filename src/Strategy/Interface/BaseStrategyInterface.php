<?php

namespace App\Strategy\Interface;

use App\Entity\Instrument;

interface BaseStrategyInterface
{
    public function setInstrument(Instrument $instrument);
    public function getInstrument(): Instrument;
    public function setBalance(float $balance);
    public function getBalance(): float;
    public function printingMessage(string $msg);
    public function getPrintingMessage(): string;
    public function cleanPrintingMessage(): bool;
    public function redisMessage($operation, $price, $balance, $time = false): bool;
    public function getRedisMessage(): array | null;
    public function cleanRedisMessage(): bool;
    public function operation($type, $lots): bool;
    public function getOperation(): array | null;
    public function cleanOperation(): bool;
}