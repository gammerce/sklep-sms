<?php
namespace App\Models;

use App\Payment\General\PaymentOption;
use App\Payment\General\PaymentSelect;

class Purchase
{
    const PAYMENT_PRICE_DIRECT_BILLING = "direct_billing_price";
    const PAYMENT_PRICE_SMS = "sms_price";
    const PAYMENT_PRICE_TRANSFER = "transfer_price";
    const PAYMENT_PAYMENT_ID = "payment_id";
    const PAYMENT_SMS_CODE = "sms_code";

    const ORDER_QUANTITY = "quantity";
    const ORDER_SERVER = "server";

    private string $id;

    /**
     * ID of row from ss_services table
     *
     * @var string|null
     */
    private ?string $serviceId = null;

    /** @var User */
    public $user;

    private ?string $email = null;

    /**
     * List of available payment platforms
     */
    private PaymentSelect $paymentSelect;

    private ?PaymentOption $paymentOption = null;

    /**
     * Payment details like method, sms_code et.c
     */
    private array $payment = [];

    /**
     * Order details like auth_data, password etc.
     */
    private array $order = [];

    /** @var PromoCode|null */
    private $promoCode = null;

    private ?string $comment = null;

    /**
     * Purchase description ( useful for transfer payments )
     */
    private ?string $transferDescription = null;

    /**
     * Platform from which the purchase was made
     */
    private ?string $platform;

    /**
     * IP from which the purchase was made
     */
    private ?string $ip;

    /**
     * Attempt to finalize purchase has been made
     */
    private bool $isAttempted = false;

    /**
     * Transaction has been deleted
     */
    private bool $isDeleted = false;

    /**
     * @param User $user
     * @param string $ip
     * @param string $platform
     */
    public function __construct(User $user, $ip, $platform)
    {
        $this->id = generate_id(32);
        $this->user = $user;
        $this->ip = $ip;
        $this->platform = $platform;
        $this->paymentSelect = new PaymentSelect();
    }

    /**
     * @return string|null
     */
    public function getServiceId()
    {
        return $this->serviceId;
    }

    /**
     * @param string $serviceId
     * @return self
     */
    public function setServiceId($serviceId): self
    {
        $this->serviceId = (string) $serviceId;
        return $this;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getOrder($key)
    {
        return array_get($this->order, $key);
    }

    /**
     * @param array $order
     * @return self
     */
    public function setOrder(array $order): self
    {
        foreach ($order as $key => $value) {
            $this->order[$key] = $value;
        }

        return $this;
    }

    public function getOrderList(): array
    {
        return $this->order;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getPayment($key)
    {
        return array_get($this->payment, $key);
    }

    /**
     * @param array $payment
     * @return self
     */
    public function setPayment(array $payment): self
    {
        foreach ($payment as $key => $value) {
            $this->payment[$key] = $value;
        }

        return $this;
    }

    public function getPaymentList(): array
    {
        return $this->payment;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return self
     */
    public function setEmail($email): self
    {
        $this->email = (string) $email;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment($comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    public function getTransferDescription(): ?string
    {
        return $this->transferDescription;
    }

    /**
     * @param string $transferDescription
     * @return self
     */
    public function setTransferDescription($transferDescription): self
    {
        $this->transferDescription = $transferDescription;
        return $this;
    }

    public function getPlatform(): ?string
    {
        return $this->platform;
    }

    public function getAddressIp(): ?string
    {
        return $this->ip;
    }

    public function getPaymentSelect(): PaymentSelect
    {
        return $this->paymentSelect;
    }

    /**
     * @param Price $price
     * @return self
     */
    public function setUsingPrice(Price $price): self
    {
        $this->setPayment([
            Purchase::PAYMENT_PRICE_SMS => as_int($price->getSmsPrice()),
            Purchase::PAYMENT_PRICE_TRANSFER => as_int($price->getTransferPrice()),
            Purchase::PAYMENT_PRICE_DIRECT_BILLING => as_int($price->getDirectBillingPrice()),
        ]);
        $this->setOrder([
            Purchase::ORDER_QUANTITY => $price->getQuantity(),
        ]);

        return $this;
    }

    public function isAttempted(): bool
    {
        return $this->isAttempted;
    }

    public function markAsAttempted(): void
    {
        $this->isAttempted = true;
    }

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    public function markAsDeleted(): void
    {
        $this->isDeleted = true;
    }

    public function getPromoCode(): ?PromoCode
    {
        return $this->promoCode;
    }

    /**
     * @param PromoCode|null $promoCode
     * @return self
     */
    public function setPromoCode(PromoCode $promoCode = null): self
    {
        $this->promoCode = $promoCode;
        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPaymentOption(): ?PaymentOption
    {
        return $this->paymentOption;
    }

    /**
     * @param PaymentOption $paymentOption
     * @return self
     */
    public function setPaymentOption(PaymentOption $paymentOption): self
    {
        $this->paymentOption = $paymentOption;
        return $this;
    }
}
