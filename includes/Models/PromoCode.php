<?php
namespace App\Models;

use App\PromoCode\QuantityType;
use App\Support\PriceTextService;
use DateTime;
use UnexpectedValueException;

class PromoCode
{
    /** @var int */
    private $id;

    /** @var string */
    private $code;

    private QuantityType $quantityType;

    /** @var int */
    private $quantity;

    private DateTime $createdAt;

    /** @var string|null */
    private $service;

    /** @var int|null */
    private $server;

    /** @var int|null */
    private $userId;

    /** @var int */
    private $usageCount;

    /** @var int|null */
    private $usageLimit;

    private ?DateTime $expiresAt;

    public function __construct(
        $id,
        $code,
        QuantityType $quantityType,
        $quantity,
        DateTime $createdAt,
        $usageCount = 0,
        $usageLimit = null,
        DateTime $expiresAt = null,
        $service = null,
        $server = null,
        $userId = null
    ) {
        $this->id = $id;
        $this->code = $code;
        $this->quantityType = $quantityType;
        $this->quantity = $quantity;
        $this->service = $service;
        $this->server = $server;
        $this->userId = $userId;
        $this->createdAt = $createdAt;
        $this->usageCount = $usageCount;
        $this->usageLimit = $usageLimit;
        $this->expiresAt = $expiresAt;
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
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return int
     */
    public function getUsageCount()
    {
        return $this->usageCount;
    }

    /**
     * @return int|null
     */
    public function getRemainingUsage()
    {
        if ($this->usageLimit === null) {
            return null;
        }

        return $this->usageLimit - $this->usageCount;
    }

    /**
     * @return int|null
     */
    public function getUsageLimit()
    {
        return $this->usageLimit;
    }

    /**
     * @return DateTime|null
     */
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    /**
     * @return string
     */
    public function getQuantityFormatted()
    {
        switch ($this->quantityType) {
            case QuantityType::FIXED():
                /** @var PriceTextService $priceTextService */
                $priceTextService = app()->make(PriceTextService::class);
                return $priceTextService->getPriceText($this->quantity);

            case QuantityType::PERCENTAGE():
                return "{$this->quantity}%";

            default:
                throw new UnexpectedValueException();
        }
    }
}
