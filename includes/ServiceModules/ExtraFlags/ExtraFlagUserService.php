<?php
namespace App\ServiceModules\ExtraFlags;

use App\Models\UserService;

class ExtraFlagUserService extends UserService
{
    private ?int $serverId;
    private int $type;
    private string $authData;
    private string $password;

    public function __construct(
        $id,
        $serviceId,
        $userId,
        $expire,
        $comment,
        $serverId,
        $type,
        $authData,
        $password
    ) {
        parent::__construct($id, $serviceId, $userId, $expire, $comment);

        $this->serverId = $serverId;
        $this->type = $type;
        $this->authData = $authData;
        $this->password = $password;
    }

    public function getServerId(): ?int
    {
        return $this->serverId;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getAuthData(): string
    {
        return $this->authData;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}
