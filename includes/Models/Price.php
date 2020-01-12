<?php
namespace App\Models;

class Price
{
    /** @var int */
    private $id;

    /** @var string */
    private $service;

    /** @var int|null */
    private $server;

    /**
     * Price in grosze
     *
     * @var int
     */
    private $smsPrice;

    /**
     * Price in grosze
     *
     * @var int
     */
    private $transferPrice;

    /** @var int */
    private $quantity;

    public function __construct($id, $serviceId, $serverId, $smsPrice, $transferPrice, $quantity)
    {
        $this->id = $id;
        $this->service = $serviceId;
        $this->server = $serverId;
        $this->smsPrice = $smsPrice;
        $this->transferPrice = $transferPrice;
        $this->quantity = $quantity;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getServiceId()
    {
        return $this->service;
    }

    public function getServerId()
    {
        return $this->server;
    }

    public function getSmsPrice()
    {
        return $this->smsPrice;
    }

    public function getTransferPrice()
    {
        return $this->transferPrice;
    }

    public function getQuantity()
    {
        return $this->quantity;
    }

    public function isForever()
    {
        return $this->quantity === null;
    }
}
