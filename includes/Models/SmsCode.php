<?php
namespace App\Models;

use App\Support\Money;
use DateTime;

class SmsCode
{
    /** @var int */
    private $id;

    /** @var string */
    private $code;

    /** @var Money */
    private $smsPrice;

    /** @var bool */
    private $free;

    /** @var DateTime|null */
    private $expiresAt;

    public function __construct($id, $code, Money $smsPrice, $free, DateTime $expiresAt = null)
    {
        $this->id = $id;
        $this->code = $code;
        $this->smsPrice = $smsPrice;
        $this->free = $free;
        $this->expiresAt = $expiresAt;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return Money
     */
    public function getSmsPrice()
    {
        return $this->smsPrice;
    }

    /**
     * @return bool
     */
    public function isFree()
    {
        return $this->free;
    }

    /**
     * @return DateTime|null
     */
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }
}
