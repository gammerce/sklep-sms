<?php
namespace App\Models;

class UserService
{
    /** @var int */
    private $id;

    /** @var string */
    private $serviceId;

    /** @var int|null */
    private $userId;

    /**
     * Timestamp or -1 when forever
     *
     * @var int
     */
    private $expire;

    public function __construct($id, $serviceId, $userId, $expire)
    {
        $this->id = $id;
        $this->serviceId = $serviceId;
        $this->userId = $userId;
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
     * @return int|null
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @return int
     */
    public function getExpire()
    {
        return $this->expire;
    }

    /**
     * @return bool
     */
    public function isForever()
    {
        return $this->expire === -1;
    }
}
