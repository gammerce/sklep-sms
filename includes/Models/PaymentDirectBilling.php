<?php
namespace App\Models;

use App\Support\Money;

class PaymentDirectBilling
{
    private int $id;
    private string $externalId;
    private Money $income;
    private Money $cost;
    private string $ip;
    private string $platform;
    private bool $free;

    public function __construct(
        int $id,
        string $externalId,
        Money $income,
        Money $cost,
        string $ip,
        string $platform,
        bool $free
    ) {
        $this->id = $id;
        $this->externalId = $externalId;
        $this->income = $income;
        $this->cost = $cost;
        $this->ip = $ip;
        $this->platform = $platform;
        $this->free = $free;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function getIncome(): Money
    {
        return $this->income;
    }

    public function getCost(): Money
    {
        return $this->cost;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function getPlatform(): string
    {
        return $this->platform;
    }

    public function isFree(): bool
    {
        return $this->free;
    }
}
