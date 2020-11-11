<?php
namespace App\Models;

use App\Support\Money;

class Price
{
    /** @var int */
    private $id;

    /** @var string */
    private $serviceId;

    /** @var int|null */
    private $serverId;

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
        $serverId = null,
        Money $smsPrice = null,
        Money $transferPrice = null,
        Money $directBillingPrice = null,
        $quantity = null,
        $discount = null
    ) {
        $this->id = $id;
        $this->serviceId = $serviceId;
        $this->serverId = $serverId;
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
        return $this->serviceId;
    }

    /**
     * @return int|null
     */
    public function getServerId()
    {
        return $this->serverId;
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
        return $this->serverId === null;
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
