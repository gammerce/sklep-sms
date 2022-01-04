<?php

namespace App\Models;

use App\Support\Money;

class PurchaseItem
{
    private string $serviceId;
    private string $serviceName;
    private Money $price;
    private ?int $taxRate;

    public function __construct(
        string $serviceId,
        string $serviceName,
        Money $price,
        ?int $taxRate = null
    ) {
        $this->serviceId = $serviceId;
        $this->serviceName = $serviceName;
        $this->price = $price;
        $this->taxRate = $taxRate;
    }

    public function getServiceId(): string
    {
        return $this->serviceId;
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    public function getPrice(): Money
    {
        return $this->price;
    }

    public function getTaxRate(): ?int
    {
        return $this->taxRate;
    }
}
