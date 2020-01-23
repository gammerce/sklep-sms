<?php
namespace App\Models;

class SmsCode
{
    /** @var int */
    private $id;

    /** @var string */
    private $code;

    /** @var int */
    private $smsPrice;

    /** @var bool */
    private $free;

    public function __construct($id, $code, $smsPrice, $free)
    {
        $this->id = (int) $id;
        $this->code = (string) $code;
        $this->smsPrice = (int) $smsPrice;
        $this->free = (bool) $free;
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
     * @return int
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
}
