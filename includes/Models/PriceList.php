<?php
namespace App\Models;

class PriceList
{
    /** @var int */
    private $id;

    /** @var string */
    private $service;

    /** @var int */
    private $tariff;

    /** @var int */
    private $amount;

    /** @var int */
    private $server;

    public function __construct($id, $service, $tariff, $amount, $server)
    {
        $this->id = $id;
        $this->service = $service;
        $this->tariff = $tariff;
        $this->amount = $amount;
        $this->server = $server;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getService()
    {
        return $this->service;
    }

    public function getTariff()
    {
        return $this->tariff;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function getServer()
    {
        return $this->server;
    }
}
