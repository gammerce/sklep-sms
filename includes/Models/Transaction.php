<?php
namespace App\Models;

use App\Payment\General\PaymentMethod;
use App\Support\Money;

class Transaction
{
    /** @var int */
    private $id;

    /** @var int */
    private $userId;

    /** @var string */
    private $userName;

    /** @var string */
    private $paymentMethod;

    /** @var string */
    private $paymentId;

    /** @var string */
    private $externalPaymentId;

    /** @var string */
    private $serviceId;

    /** @var int */
    private $serverId;

    /** @var float */
    private $quantity;

    /** @var string */
    private $authData;

    /** @var string */
    private $email;

    /** @var string|null */
    private $promoCode;

    /** @var array|null */
    private $extraData;

    /** @var string */
    private $ip;

    /** @var string */
    private $platform;

    /** @var Money|null */
    private $income;

    /** @var Money|null */
    private $cost;

    /** @var int */
    private $adminId;

    /** @var string */
    private $adminName;

    /** @var string */
    private $smsCode;

    /** @var string */
    private $smsText;

    /** @var string */
    private $smsNumber;

    /** @var bool */
    private $free;

    /** @var string */
    private $timestamp;

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
        $this->extraData = $extraData;
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

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @return string
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @return PaymentMethod|null
     */
    public function getPaymentMethod()
    {
        return as_payment_method($this->paymentMethod);
    }

    /**
     * @return string
     */
    public function getPaymentId()
    {
        return $this->paymentId;
    }

    /**
     * @return string
     */
    public function getExternalPaymentId()
    {
        return $this->externalPaymentId;
    }

    /**
     * @return string
     */
    public function getServiceId()
    {
        return $this->serviceId;
    }

    /**
     * @return int
     */
    public function getServerId()
    {
        return $this->serverId;
    }

    /**
     * @return float
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @return bool
     */
    public function isForever()
    {
        return $this->quantity === -1;
    }

    /**
     * @return string
     */
    public function getAuthData()
    {
        return $this->authData;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return string|null
     */
    public function getPromoCode()
    {
        return $this->promoCode;
    }

    /**
     * @return array|null
     */
    public function getExtraData()
    {
        return $this->extraData;
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function getExtraDatum($key)
    {
        return array_get($this->extraData, $key);
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @return string
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * @return Money|null
     */
    public function getIncome()
    {
        return $this->income;
    }

    /**
     * @return Money|null
     */
    public function getCost()
    {
        return $this->cost;
    }

    /**
     * @return int
     */
    public function getAdminId()
    {
        return $this->adminId;
    }

    /**
     * @return string
     */
    public function getAdminName()
    {
        return $this->adminName;
    }

    /**
     * @return string
     */
    public function getSmsCode()
    {
        return $this->smsCode;
    }

    /**
     * @return string
     */
    public function getSmsText()
    {
        return $this->smsText;
    }

    /**
     * @return string
     */
    public function getSmsNumber()
    {
        return $this->smsNumber;
    }

    /**
     * @return bool
     */
    public function isFree()
    {
        return $this->free;
    }

    /**
     * @return string
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }
}
