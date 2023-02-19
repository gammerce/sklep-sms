<?php
namespace App\Models;

use App\Payment\General\PaymentMethod;

class BoughtService
{
    private int $id;

    /**
     * 0 means null
     */
    private ?int $userId;
    private string $method;
    private string $paymentId;
    private ?string $invoiceId;
    private string $serviceId;

    /**
     * 0 means null
     */
    private ?int $serverId;

    private string $amount;
    private string $authData;
    private string $email;
    private ?string $promoCode;
    private array $extraData;

    public function __construct(
        $id,
        $userId,
        $method,
        $paymentId,
        $invoiceId,
        $serviceId,
        $serverId,
        $amount,
        $authData,
        $email,
        $promoCode,
        array $extraData
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->method = $method;
        $this->paymentId = $paymentId;
        $this->invoiceId = $invoiceId;
        $this->serviceId = $serviceId;
        $this->serverId = $serverId;
        $this->amount = $amount;
        $this->authData = $authData;
        $this->email = $email;
        $this->promoCode = $promoCode;
        $this->extraData = $extraData;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getMethod(): ?PaymentMethod
    {
        return as_payment_method($this->method);
    }

    public function getPaymentId(): string
    {
        return $this->paymentId;
    }

    public function getInvoiceId(): string
    {
        return $this->invoiceId;
    }

    public function getServiceId(): string
    {
        return $this->serviceId;
    }

    public function getServerId(): ?int
    {
        return $this->serverId;
    }

    public function getAmount(): string
    {
        return $this->amount;
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
}
