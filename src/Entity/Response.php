<?php

namespace App\Entity;

class Response
{
    private $response;
    private $status;

    public function __construct($response, $status)
    {
        $this->response = $response;
        $this->status = $status;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getData()
    {
        return json_decode($this->response, true);
    }
}