<?php
namespace App\Models;

// TODO Display discount value in purchase form

class Price
{
    /** @var int */
    private $id;

    /** @var string */
    private $service;

    /** @var int|null */
    private $server;

    /** @var int|null */
    private $smsPrice;

    /** @var int|null */
    private $transferPrice;

    /** @var int|null */
    private $directBillingPrice;

    /** @var int|null */
    private $quantity;

    /** @var int|null */
    private $discount;

    public function __construct(
        $id,
        $serviceId,
        $serverId,
        $smsPrice,
        $transferPrice,
        $directBillingPrice,
        $quantity,
        $discount
    ) {
        $this->id = $id;
        $this->service = $serviceId;
        $this->server = $serverId;
        $this->smsPrice = $smsPrice;
        $this->transferPrice = $transferPrice;
        $this->directBillingPrice = $directBillingPrice;
        $this->quantity = $quantity;
        $this->discount = $discount;
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
        return $this->directBillingPrice;
    }

    public function hasDirectBillingPrice()
    {
        return $this->directBillingPrice !== null;
    }

    public function getQuantity()
    {
        return $this->quantity;
    }

    public function getDiscount()
    {
        return $this->discount;
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
