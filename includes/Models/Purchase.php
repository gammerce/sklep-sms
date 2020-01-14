<?php
namespace App\Models;

/**
 * Obiekty tej klasy są używane podczas przeprowadzania zakupu
 */
class Purchase
{
    const METHOD_SMS = "sms";
    const METHOD_TRANSFER = "transfer";
    const METHOD_SERVICE_CODE = "service_code";
    const METHOD_WALLET = "wallet";

    const PAYMENT_TRANSFER_PRICE = "transfer_price";
    const ORDER_QUANTITY = "quantity";
    const ORDER_FOREVER = "forever";

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

    /**
     * @deprecated
     * @var Tariff
     */
    private $tariff = null;

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

    public function setOrder($order)
    {
        foreach ($order as $key => $value) {
            $this->order[$key] = $value;
        }
    }

    /**
     * @deprecated
     * @param Tariff $tariff
     */
    public function setTariff($tariff)
    {
        $this->tariff = $tariff;
    }

    public function setPrice(Price $price = null)
    {
        $this->price = $price;
    }

    public function setEmail($email)
    {
        $this->email = (string) $email;
    }

    public function setPayment($payment)
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
     *
     * @return mixed
     */
    public function getOrder($key = null)
    {
        if ($key === null) {
            return $this->order;
        }

        return array_get($this->order, $key);
    }

    /**
     * @param string|null $key
     *
     * @return mixed
     */
    public function getPayment($key = null)
    {
        if ($key === null) {
            return $this->payment;
        }

        return array_get($this->payment, $key);
    }

    /**
     * @deprecated
     * @return Tariff
     */
    public function getTariff()
    {
        return $this->tariff;
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
