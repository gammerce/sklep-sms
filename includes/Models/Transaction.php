<?php
namespace App\Models;

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
    private $serviceId;

    /** @var int */
    private $serverId;

    /** @var float */
    private $quantity;

    /** @var string */
    private $authData;

    /** @var string */
    private $email;

    /** @var array|null */
    private $extraData;

    /** @var string */
    private $ip;

    /** @var string */
    private $platform;

    /** @var int */
    private $income;

    /** @var int */
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
    private $serviceCode;

    /** @var string */
    private $timestamp;

    public function __construct(
        $id,
        $userId,
        $userName,
        $paymentMethod,
        $paymentId,
        $serviceId,
        $serverId,
        $quantity,
        $authData,
        $email,
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
        $serviceCode,
        $timestamp
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->userName = $userName;
        $this->paymentMethod = $paymentMethod;
        $this->paymentId = $paymentId;
        $this->serviceId = $serviceId;
        $this->serverId = $serverId;
        $this->quantity = $quantity;
        $this->authData = $authData;
        $this->email = $email;
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
        $this->serviceCode = $serviceCode;
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
     * @return string
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
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
     * @return int
     */
    public function getIncome()
    {
        return $this->income;
    }

    /**
     * @return int
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
    public function getServiceCode()
    {
        return $this->serviceCode;
    }

    /**
     * @return string
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }
}
