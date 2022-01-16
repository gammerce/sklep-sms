<?php

namespace App\Payment\Invoice;

use App\Support\Money;

class PurchaseItem
{
    private string $serviceId;
    private string $serviceName;
    private Money $price;
    private int $taxRate;
    private ?string $flatRateTax;
    private ?string $PKWiUSymbol;

    public function __construct(
        string $serviceId,
        string $serviceName,
        Money $price,
        int $taxRate,
        ?string $flatRateTax,
        ?string $PKWiUSymbol
    ) {
        $this->serviceId = $serviceId;
        $this->serviceName = $serviceName;
        $this->price = $price;
        $this->taxRate = $taxRate;
        $this->flatRateTax = $flatRateTax;
        $this->PKWiUSymbol = $PKWiUSymbol;
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

    public function getTaxRate(): int
    {
        return $this->taxRate;
    }

    public function getFlatRateTax(): ?string
    {
        return $this->flatRateTax;
    }

    public function getPKWiUSymbol()
    {
        return $this->PKWiUSymbol;
    }
}
