<?php
namespace App\Models;

class UserService
{
    private int $id;
    private string $serviceId;
    private ?int $userId;

    /**
     * Timestamp or -1 when forever
     */
    private int $expire;

    public function __construct($id, $serviceId, $userId, $expire)
    {
        $this->id = $id;
        $this->serviceId = $serviceId;
        $this->userId = $userId;
        $this->expire = $expire;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getServiceId(): string
    {
        return $this->serviceId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getExpire(): int
    {
        return $this->expire;
    }

    public function isForever(): bool
    {
        return $this->expire === -1;
    }
}
