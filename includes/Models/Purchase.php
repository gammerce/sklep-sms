<?php
namespace App\Models;

class Purchase
{
    const METHOD_ADMIN = "admin";
    const METHOD_SMS = "sms";
    const METHOD_TRANSFER = "transfer";
    const METHOD_SERVICE_CODE = "service_code";
    const METHOD_WALLET = "wallet";

    const PAYMENT_SMS_PRICE = "sms_price";
    const PAYMENT_TRANSFER_PRICE = "transfer_price";
    const PAYMENT_SMS_DISABLED = "no_sms";
    const PAYMENT_TRANSFER_DISABLED = "no_transfer";
    const PAYMENT_WALLET_DISABLED = "no_wallet";
    const PAYMENT_SERVICE_CODE_DISABLED = "no_code";
    const PAYMENT_METHOD = "method";
    const PAYMENT_PAYMENT_ID = "payment_id";
    const PAYMENT_SERVICE_CODE = "service_code";
    const PAYMENT_SMS_CODE = "sms_code";
    const PAYMENT_SMS_PLATFORM = "sms_platform";
    const PAYMENT_TRANSFER_PLATFORM = "transfer_platform";

    const ORDER_QUANTITY = "quantity";
    const ORDER_SERVER = "server";

    /**
     * ID of row from ss_services table
     *
     * @var string|null
     */
    private $service = null;

    /**
     * Order details like auth_data, password etc.
     *
     * @var array
     */
    private $order = null;

    /** @var User */
    public $user;

    /** @var Price|null */
    private $price = null;

    /** @var string */
    private $email = null;

    /**
     * Payment details like method, sms_code et.c
     *
     * @var array
     */
    private $payment = null;

    /**
     * Purchase description ( useful for transfer payments )
     *
     * @var string
     */
    private $desc = null;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function setService($service)
    {
        $this->service = (string) $service;
    }

    public function setOrder(array $order)
    {
        foreach ($order as $key => $value) {
            $this->order[$key] = $value;
        }
    }

    public function setPrice(Price $price)
    {
        $this->price = $price;
        $this->setPayment([
            Purchase::PAYMENT_SMS_PRICE => $price->getSmsPrice(),
            Purchase::PAYMENT_TRANSFER_PRICE => $price->getTransferPrice(),
        ]);
        $this->setOrder([
            Purchase::ORDER_QUANTITY => $price->getQuantity(),
        ]);
    }

    public function setEmail($email)
    {
        $this->email = (string) $email;
    }

    public function setPayment(array $payment)
    {
        foreach ($payment as $key => $value) {
            $this->payment[$key] = $value;
        }
    }

    public function getService()
    {
        return $this->service;
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
     * @param string $key
     * @return mixed
     */
    public function getPayment($key)
    {
        return array_get($this->payment, $key);
    }

    /**
     * @return array
     */
    public function getPaymentList()
    {
        return $this->payment;
    }

    /**
     * @return Price|null
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
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
}
