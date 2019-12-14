<?php
namespace App\Models;

class ServiceCode
{
    /** @var int */
    private $id;

    /** @var string */
    private $code;

    /** @var string */
    private $service;

    /** @var int */
    private $server;

    /** @var int|float */
    private $tariff;

    /** @var int */
    private $uid;

    /** @var int */
    private $amount;

    /** @var string */
    private $data;

    /** @var int */
    private $timestamp;

    public function __construct(
        $id,
        $code,
        $service,
        $server,
        $tariff,
        $uid,
        $amount,
        $data,
        $timestamp
    ) {
        $this->id = $id;
        $this->code = $code;
        $this->service = $service;
        $this->server = $server;
        $this->tariff = $tariff;
        $this->uid = $uid;
        $this->amount = $amount;
        $this->data = $data;
        $this->timestamp = $timestamp;
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
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @return int
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @return int
     */
    public function getTariff()
    {
        return $this->tariff;
    }

    /**
     * @return int
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return int
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }
}
