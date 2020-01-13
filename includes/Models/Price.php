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

    public function getQuantity()
    {
        return $this->quantity;
    }

    // TODO Are we sure we need forever price?
    public function isForever()
    {
        return $this->quantity === null;
    }

    public function isForEveryServer()
    {
        return $this->server === null;
    }

    public function concernServer(Server $server)
    {
        return $this->isForEveryServer() || $this->getServerId() === $server->getId();
    }

    public function concernService(Service $service)
    {
        return $this->getServiceId() === $service->getId();
    }
}
