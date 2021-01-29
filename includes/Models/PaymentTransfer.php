<?php
namespace App\Models;

use App\Support\Money;

class PaymentTransfer
{
    private string $id;
    private Money $income;
    private Money $cost;
    private string $transferService;
    private string $ip;
    private string $platform;
    private bool $free;

    public function __construct(
        string $id,
        Money $income,
        Money $cost,
        string $transferService,
        string $ip,
        string $platform,
        bool $free
    ) {
        $this->id = $id;
        $this->income = $income;
        $this->cost = $cost;
        $this->transferService = $transferService;
        $this->ip = $ip;
        $this->platform = $platform;
        $this->free = $free;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getIncome(): Money
    {
        return $this->income;
    }

    public function getCost(): Money
    {
        return $this->cost;
    }

    public function getTransferService(): string
    {
        return $this->transferService;
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
