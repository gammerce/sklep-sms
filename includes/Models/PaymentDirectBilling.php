<?php
namespace App\Models;

class PaymentDirectBilling
{
    /** @var int */
    private $id;

    /** @var string */
    private $externalId;

    /** @var int */
    private $income;

    /** @var int */
    private $cost;

    /** @var string */
    private $ip;

    /** @var string */
    private $platform;

    /** @var bool */
    private $free;

    public function __construct($id, $externalId, $income, $cost, $ip, $platform, $free)
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
     * @return int
     */
    public function getIncome()
    {
        return $this->income;
    }

    /**
     * @return int
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
