<?php
namespace App\Models;

class Purchase
{
    const METHOD_ADMIN = "admin";
    const METHOD_DIRECT_BILLING = "direct_billing";
    const METHOD_SMS = "sms";
    const METHOD_TRANSFER = "transfer";
    const METHOD_WALLET = "wallet";

    const PAYMENT_DISABLED_DIRECT_BILLING = "no_direct_billing";
    const PAYMENT_DISABLED_SMS = "no_sms";
    const PAYMENT_DISABLED_TRANSFER = "no_transfer";
    const PAYMENT_DISABLED_WALLET = "no_wallet";
    const PAYMENT_METHOD = "method";
    const PAYMENT_PAYMENT_ID = "payment_id";
    const PAYMENT_PLATFORM_DIRECT_BILLING = "direct_billing_platform";
    const PAYMENT_PLATFORM_SMS = "sms_platform";
    const PAYMENT_PLATFORM_TRANSFER = "transfer_platform";
    const PAYMENT_PRICE_DIRECT_BILLING = "direct_billing_price";
    const PAYMENT_PRICE_SMS = "sms_price";
    const PAYMENT_PRICE_TRANSFER = "transfer_price";
    const PAYMENT_SMS_CODE = "sms_code";

    const ORDER_QUANTITY = "quantity";
    const ORDER_SERVER = "server";

    /** @var string */
    private $id;

    /**
     * ID of row from ss_services table
     *
     * @var string|null
     */
    private $serviceId;

    /** @var User */
    public $user;

    /** @var string|null */
    private $email;

    /**
     * Payment details like method, sms_code et.c
     *
     * @var array
     */
    private $payment = [];

    /**
     * Order details like auth_data, password etc.
     *
     * @var array
     */
    private $order = [];

    /**
     * @var PromoCode
     */
    private $promoCode;

    /**
     * Purchase description ( useful for transfer payments )
     * @var string|null
     */
    private $desc;

    /**
     * Attempt to finalize purchase has been made
     *
     * @var bool
     */
    private $isAttempted = false;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->id = 0;
    }

    public function getServiceId()
    {
        return $this->serviceId;
    }

    /**
     * @param string $serviceId
     */
    public function setServiceId($serviceId)
    {
        $this->serviceId = (string) $serviceId;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getOrder($key)
    {
        return array_get($this->order, $key);
    }

    public function setOrder(array $order)
    {
        foreach ($order as $key => $value) {
            $this->order[$key] = $value;
        }
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getPayment($key)
    {
        return array_get($this->payment, $key);
    }

    public function setPayment(array $payment)
    {
        foreach ($payment as $key => $value) {
            $this->payment[$key] = $value;
        }
    }

    /**
     * @return array
     */
    public function getPaymentList()
    {
        return $this->payment;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = (string) $email;
    }

    /**
     * @return string
     */
    public function getDesc()
    {
        return $this->desc;
    }

    /**
     * @param string $desc
     */
    public function setDesc($desc)
    {
        $this->desc = $desc;
    }

    public function setUsingPrice(Price $price)
    {
        $this->setPayment([
            Purchase::PAYMENT_PRICE_SMS => $price->getSmsPrice(),
            Purchase::PAYMENT_PRICE_TRANSFER => $price->getTransferPrice(),
            Purchase::PAYMENT_PRICE_DIRECT_BILLING => $price->getDirectBillingPrice(),
        ]);
        $this->setOrder([
            Purchase::ORDER_QUANTITY => $price->getQuantity(),
        ]);
    }

    /**
     * @return bool
     */
    public function isAttempted()
    {
        return $this->isAttempted;
    }

    public function markAsAttempted()
    {
        $this->isAttempted = true;
    }

    /**
     * @return PromoCode
     */
    public function getPromoCode()
    {
        return $this->promoCode;
    }

    /**
     * @param PromoCode $promoCode
     */
    public function setPromoCode(PromoCode $promoCode)
    {
        $this->promoCode = $promoCode;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }
}
