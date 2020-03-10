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

    /** @var int */
    private $smsPrice;

    /** @var int */
    private $transferPrice;

    /** @var int */
    private $directBillingPrice;

    /** @var int|null */
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

    public function hasSmsPrice()
    {
        return $this->smsPrice !== null;
    }

    public function getTransferPrice()
    {
        return $this->transferPrice;
    }

    public function hasTransferPrice()
    {
        return $this->transferPrice !== null;
    }

    public function getDirectBillingPrice()
    {
        // TODO Change it to direct billing
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

    public function isForEveryServer()
    {
        return $this->server === null;
    }

    public function concernServer($serverId)
    {
        return $this->isForEveryServer() || $this->getServerId() === $serverId;
    }

    public function concernService($serviceId)
    {
        return $this->getServiceId() === $serviceId;
    }
}
