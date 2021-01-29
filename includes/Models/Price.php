<?php
namespace App\Models;

use App\Support\Money;

class Price
{
    private int $id;
    private string $serviceId;
    private ?int $serverId;
    private ?Money $smsPrice;
    private ?Money $transferPrice;
    private ?Money $directBillingPrice;
    private ?int $discount;

    /**
     * Null means infinity/forever
     */
    private ?int $quantity;

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

    public function getId(): int
    {
        return $this->id;
    }

    public function getServiceId(): string
    {
        return $this->serviceId;
    }

    public function getServerId(): ?int
    {
        return $this->serverId;
    }

    public function getSmsPrice(): ?Money
    {
        return $this->smsPrice;
    }

    public function hasSmsPrice(): bool
    {
        return $this->smsPrice !== null;
    }

    public function getTransferPrice(): ?Money
    {
        return $this->transferPrice;
    }

    public function hasTransferPrice(): bool
    {
        return $this->transferPrice !== null;
    }

    public function getDirectBillingPrice(): ?Money
    {
        return $this->directBillingPrice;
    }

    public function hasDirectBillingPrice(): bool
    {
        return $this->directBillingPrice !== null;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function getDiscount(): ?int
    {
        return $this->discount;
    }

    public function isForever(): bool
    {
        return $this->quantity === null;
    }

    public function isForEveryServer(): bool
    {
        return $this->serverId === null;
    }

    public function concernServer(?int $serverId): bool
    {
        return $this->isForEveryServer() || $this->getServerId() === $serverId;
    }

    public function concernService(?string $serviceId): bool
    {
        return $this->getServiceId() === $serviceId;
    }
}
