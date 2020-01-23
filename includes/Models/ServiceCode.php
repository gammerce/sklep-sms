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

    /** @var int|null */
    private $server;

    /** @var int */
    private $price;

    /** @var int|null */
    private $uid;

    /** @var int */
    private $timestamp;

    public function __construct($id, $code, $service, $price, $server, $uid, $timestamp)
    {
        $this->id = $id;
        $this->code = $code;
        $this->service = $service;
        $this->server = $server;
        $this->price = $price;
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
     * @return int
     */
    public function getPriceId()
    {
        return $this->price;
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
    public function getTimestamp()
    {
        return $this->timestamp;
    }
}
