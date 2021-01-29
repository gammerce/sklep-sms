<?php
namespace App\Models;

use App\Support\Money;
use DateTime;

class SmsCode
{
    private int $id;
    private string $code;
    private Money $smsPrice;
    private bool $free;
    private ?DateTime $expiresAt;

    public function __construct(
        int $id,
        string $code,
        Money $smsPrice,
        bool $free,
        ?DateTime $expiresAt = null
    ) {
        $this->id = $id;
        $this->code = $code;
        $this->smsPrice = $smsPrice;
        $this->free = $free;
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

    public function getSmsPrice(): Money
    {
        return $this->smsPrice;
    }

    public function isFree(): bool
    {
        return $this->free;
    }

    public function getExpiresAt(): ?DateTime
    {
        return $this->expiresAt;
    }
}
