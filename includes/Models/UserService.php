<?php
namespace App\Models;

class UserService
{
    /** @var int */
    private $id;

    /** @var string */
    private $serviceId;

    /** @var int */
    private $uid;

    /** @var int */
    private $expire;

    public function __construct($id, $serviceId, $uid, $expire)
    {
        $this->id = $id;
        $this->serviceId = $serviceId;
        $this->uid = $uid;
        $this->expire = $expire;
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
    public function getServiceId()
    {
        return $this->serviceId;
    }

    /**
     * @return int
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @return int
     */
    public function getExpire()
    {
        return $this->expire;
    }
}