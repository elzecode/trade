<?php

namespace App\Entity;

class Portfolio
{
    private $payload;

    public function __construct(Response $response)
    {
        $this->payload = $response->getData()['payload'] ?? [];
    }

    public function getPositionByTicker($ticker)
    {
        $findPosition = null;
        if (isset($this->payload['positions'])) {
            foreach ($this->payload['positions'] as $position) {
                if (isset($position['ticker']) && $position['ticker'] == $ticker) {
                    $findPosition = $position;
                    break;
                }
            }
        }

        return $findPosition;
    }

    public function getInstrumentByTicker($ticker)
    {
        $position = $this->getPositionByTicker($ticker);
        return $position ? new Instrument(
            $position['figi'],
            $position['ticker'],
            $position['name'],
            $position['averagePositionPrice']['currency'],
            $position['balance'],
            $position['averagePositionPrice']['value']
        ) : 0;

    }
}