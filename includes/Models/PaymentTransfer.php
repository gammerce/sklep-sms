<?php
namespace App\Models;

use App\Support\Money;

class PaymentTransfer
{
    /** @var string */
    private $id;

    /** @var Money */
    private $income;

    /** @var Money */
    private $cost;

    /** @var string */
    private $transferService;

    /** @var string */
    private $ip;

    /** @var string */
    private $platform;

    /** @var bool */
    private $free;

    public function __construct(
        $id,
        Money $income,
        Money $cost,
        $transferService,
        $ip,
        $platform,
        $free
    ) {
        $this->id = $id;
        $this->income = $income;
        $this->cost = $cost;
        $this->transferService = $transferService;
        $this->ip = $ip;
        $this->platform = $platform;
        $this->free = $free;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
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
    public function getTransferService()
    {
        return $this->transferService;
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
