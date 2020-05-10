<?php
namespace App\Models;

use App\PromoCode\QuantityType;

class PromoCode
{
    /** @var int */
    private $id;

    /** @var string */
    private $code;

    /** @var QuantityType */
    private $quantityType;

    /** @var int */
    private $quantity;

    /** @var string|null */
    private $service;

    /** @var int|null */
    private $server;

    /** @var int|null */
    private $uid;

    /** @var string */
    private $timestamp;

    public function __construct(
        $id,
        $code,
        QuantityType $quantityType,
        $quantity,
        $service,
        $server,
        $uid,
        $timestamp
    ) {
        $this->id = $id;
        $this->code = $code;
        $this->quantityType = $quantityType;
        $this->quantity = $quantity;
        $this->service = $service;
        $this->server = $server;
        $this->uid = $uid;
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
    public function getServiceId()
    {
        return $this->service;
    }

    /**
     * @return int
     */
    public function getServerId()
    {
        return $this->server;
    }

    /**
     * @return QuantityType
     */
    public function getQuantityType()
    {
        return $this->quantityType;
    }

    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @return int
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @return string
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }
}
