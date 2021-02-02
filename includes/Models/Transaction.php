<?php
namespace App\Models;

use App\Payment\General\PaymentMethod;
use App\Support\Money;

class Transaction
{
    private int $id;
    private ?int $userId;
    private ?string $userName;
    private string $paymentMethod;
    private string $paymentId;
    private ?string $externalPaymentId;
    private string $serviceId;
    private int $serverId;
    private float $quantity;
    private string $authData;
    private string $email;
    private ?string $promoCode;
    private array $extraData;
    private string $ip;
    private string $platform;
    private ?Money $income;
    private ?Money $cost;
    private ?int $adminId;
    private ?string $adminName;
    private ?string $smsCode;
    private ?string $smsText;
    private ?string $smsNumber;
    private bool $free;
    private string $timestamp;

    public function __construct(
        $id,
        $userId,
        $userName,
        $paymentMethod,
        $paymentId,
        $externalPaymentId,
        $serviceId,
        $serverId,
        $quantity,
        $authData,
        $email,
        $promoCode,
        $extraData,
        $ip,
        $platform,
        $income,
        $cost,
        $adminId,
        $adminName,
        $smsCode,
        $smsText,
        $smsNumber,
        $free,
        $timestamp
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->userName = $userName;
        $this->paymentMethod = $paymentMethod;
        $this->paymentId = $paymentId;
        $this->externalPaymentId = $externalPaymentId;
        $this->serviceId = $serviceId;
        $this->serverId = $serverId;
        $this->quantity = $quantity;
        $this->authData = $authData;
        $this->email = $email;
        $this->promoCode = $promoCode;
        $this->extraData = $extraData ?: [];
        $this->ip = $ip;
        $this->platform = $platform;
        $this->income = $income;
        $this->cost = $cost;
        $this->adminId = $adminId;
        $this->adminName = $adminName;
        $this->smsCode = $smsCode;
        $this->smsText = $smsText;
        $this->smsNumber = $smsNumber;
        $this->free = $free;
        $this->timestamp = $timestamp;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }

    public function getPaymentMethod(): PaymentMethod
    {
        return as_payment_method($this->paymentMethod);
    }

    public function getPaymentId(): string
    {
        return $this->paymentId;
    }

    public function getExternalPaymentId(): ?string
    {
        return $this->externalPaymentId;
    }

    public function getServiceId(): string
    {
        return $this->serviceId;
    }

    public function getServerId(): int
    {
        return $this->serverId;
    }

    public function getQuantity(): float
    {
        return $this->quantity;
    }

    public function isForever(): bool
    {
        return $this->quantity === -1;
    }

    public function getAuthData(): string
    {
        return $this->authData;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPromoCode(): ?string
    {
        return $this->promoCode;
    }

    public function getExtraData(): array
    {
        return $this->extraData;
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function getExtraDatum($key): mixed
    {
        return array_get($this->extraData, $key);
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function getPlatform(): string
    {
        return $this->platform;
    }

    public function getIncome(): ?Money
    {
        return $this->income;
    }

    public function getCost(): ?Money
    {
        return $this->cost;
    }

    public function getAdminId(): ?int
    {
        return $this->adminId;
    }

    public function getAdminName(): ?string
    {
        return $this->adminName;
    }

    public function getSmsCode(): ?string
    {
        return $this->smsCode;
    }

    public function getSmsText(): ?string
    {
        return $this->smsText;
    }

    public function getSmsNumber(): ?string
    {
        return $this->smsNumber;
    }

    public function isFree(): bool
    {
        return $this->free;
    }

    public function getTimestamp(): string
    {
        return $this->timestamp;
    }
}
