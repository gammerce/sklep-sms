<?php
namespace App\Models;

use App\PromoCode\QuantityType;
use App\Support\PriceTextService;
use DateTime;
use UnexpectedValueException;

class PromoCode
{
    private int $id;
    private string $code;
    private QuantityType $quantityType;
    private int $quantity;
    private DateTime $createdAt;
    private ?string $serviceId;
    private ?int $server;
    private ?int $userId;
    private int $usageCount;

    /**
     * null means no limit
     */
    private ?int $usageLimit;

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
        $serviceId = null,
        $server = null,
        $userId = null
    ) {
        $this->id = $id;
        $this->code = $code;
        $this->quantityType = $quantityType;
        $this->quantity = $quantity;
        $this->serviceId = $serviceId;
        $this->server = $server;
        $this->userId = $userId;
        $this->createdAt = $createdAt;
        $this->usageCount = $usageCount;
        $this->usageLimit = $usageLimit;
        $this->expiresAt = $expiresAt;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getServiceId(): ?string
    {
        return $this->serviceId;
    }

    public function getServerId(): ?int
    {
        return $this->server;
    }

    public function getQuantityType(): QuantityType
    {
        return $this->quantityType;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUsageCount(): int
    {
        return $this->usageCount;
    }

    public function getRemainingUsage(): ?int
    {
        if ($this->usageLimit === null) {
            return null;
        }

        return $this->usageLimit - $this->usageCount;
    }

    public function getUsageLimit(): ?int
    {
        return $this->usageLimit;
    }

    public function getExpiresAt(): ?DateTime
    {
        return $this->expiresAt;
    }

    public function getQuantityFormatted(): string
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
