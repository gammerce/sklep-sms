<?php
namespace App\Models;

class PaymentCode
{
    /** @var int */
    private $id;

    /** @var string */
    private $code;

    /** @var string */
    private $ip;

    /** @var string */
    private $platform;

    public function __construct($id, $code, $ip, $platform)
    {
        $this->id = $id;
        $this->code = $code;
        $this->ip = $ip;
        $this->platform = $platform;
    }

    /** @return int */
    public function getId()
    {
        return $this->id;
    }

    /** @return string */
    public function getCode()
    {
        return $this->code;
    }

    /** @return string */
    public function getIp()
    {
        return $this->ip;
    }

    /** @return string */
    public function getPlatform()
    {
        return $this->platform;
    }
}
