<?php
namespace App\Models;

class BoughtService
{
    /** @var int */
    private $id;

    /** @var int */
    private $userId;

    /** @var string */
    private $method;

    /** @var string */
    private $paymentId;

    /** @var string */
    private $service;

    /** @var int */
    private $server;

    /** @var string */
    private $amount;

    /** @var string */
    private $authData;

    /** @var string */
    private $email;

    /** @var string|null */
    private $promoCode;

    /** @var array */
    private $extraData;

    public function __construct(
        $id,
        $userId,
        $method,
        $paymentId,
        $service,
        $server,
        $amount,
        $authData,
        $email,
        $promoCode,
        $extraData
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->method = $method;
        $this->paymentId = $paymentId;
        $this->service = $service;
        $this->server = $server;
        $this->amount = $amount;
        $this->authData = $authData;
        $this->email = $email;
        $this->promoCode = $promoCode;
        $this->extraData = $extraData;
    }

    /** @return int */
    public function getId()
    {
        return $this->id;
    }

    /** @return int */
    public function getUserId()
    {
        return $this->userId;
    }

    /** @return string */
    public function getMethod()
    {
        return $this->method;
    }

    /** @return string */
    public function getPaymentId()
    {
        return $this->paymentId;
    }

    /** @return string */
    public function getServiceId()
    {
        return $this->service;
    }

    /** @return int */
    public function getServerId()
    {
        return $this->server;
    }

    /** @return string */
    public function getAmount()
    {
        return $this->amount;
    }

    /** @return string */
    public function getAuthData()
    {
        return $this->authData;
    }

    /** @return string */
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

    /** @return array */
    public function getExtraData()
    {
        return $this->extraData;
    }
}
