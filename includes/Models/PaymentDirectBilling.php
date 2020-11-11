<?php
namespace App\Models;

use App\Support\Money;

class PaymentDirectBilling
{
    /** @var int */
    private $id;

    /** @var string */
    private $externalId;

    /** @var Money */
    private $income;

    /** @var Money */
    private $cost;

    /** @var string */
    private $ip;

    /** @var string */
    private $platform;

    /** @var bool */
    private $free;

    public function __construct($id, $externalId, Money $income, Money $cost, $ip, $platform, $free)
    {
        $this->id = $id;
        $this->externalId = $externalId;
        $this->income = $income;
        $this->cost = $cost;
        $this->ip = $ip;
        $this->platform = $platform;
        $this->free = $free;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getExternalId()
    {
        return $this->externalId;
    }

    /**
     * @return Money
     */
    public function getIncome()
    {
        return $this->income;
    }

    /**
     * @return Money
     */
    public function getCost()
    {
        return $this->cost;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @return string
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * @return bool
     */
    public function isFree()
    {
        return $this->free;
    }
}
