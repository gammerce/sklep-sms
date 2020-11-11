<?php
namespace App\Models;

use App\Support\Money;

class Price
{
    /** @var int */
    private $id;

    /** @var string */
    private $service;

    /** @var int|null */
    private $server;

    /** @var Money|null */
    private $smsPrice;

    /** @var Money|null */
    private $transferPrice;

    /** @var Money|null */
    private $directBillingPrice;

    /**
     * Null means infinity/forever
     *
     * @var int|null
     */
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
    public function getServiceId()
    {
        return $this->service;
    }

    /**
     * @return int|null
     */
    public function getServerId()
    {
        return $this->server;
    }

    /**
     * @return Money|null
     */
    public function getSmsPrice()
    {
        return $this->smsPrice;
    }

    /**
     * @return bool
     */
    public function hasSmsPrice()
    {
        return $this->smsPrice !== null;
    }

    /**
     * @return Money|null
     */
    public function getTransferPrice()
    {
        return $this->transferPrice;
    }

    /**
     * @return bool
     */
    public function hasTransferPrice()
    {
        return $this->transferPrice !== null;
    }

    /**
     * @return Money|null
     */
    public function getDirectBillingPrice()
    {
        return $this->directBillingPrice;
    }

    /**
     * @return bool
     */
    public function hasDirectBillingPrice()
    {
        return $this->directBillingPrice !== null;
    }

    /**
     * @return int|null
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @return int|null
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * @return bool
     */
    public function isForever()
    {
        return $this->quantity === null;
    }

    /**
     * @return bool
     */
    public function isForEveryServer()
    {
        return $this->server === null;
    }

    /**
     * @param int|null $serverId
     * @return bool
     */
    public function concernServer($serverId)
    {
        return $this->isForEveryServer() || $this->getServerId() === $serverId;
    }

    /**
     * @param string $serviceId
     * @return bool
     */
    public function concernService($serviceId)
    {
        return $this->getServiceId() === $serviceId;
    }
}
