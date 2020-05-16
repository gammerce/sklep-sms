<?php
namespace App\ServiceModules\ExtraFlags;

use App\Models\UserService;

class ExtraFlagUserService extends UserService
{
    /** @var int */
    private $serverId;

    /** @var int */
    private $type;

    /** @var string */
    private $authData;

    /** @var string */
    private $password;

    public function __construct(
        $id,
        $serviceId,
        $userId,
        $expire,
        $serverId,
        $type,
        $authData,
        $password
    ) {
        parent::__construct($id, $serviceId, $userId, $expire);

        $this->serverId = $serverId;
        $this->type = $type;
        $this->authData = $authData;
        $this->password = $password;
    }

    /**
     * @return int
     */
    public function getServerId()
    {
        return $this->serverId;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
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
    public function getPassword()
    {
        return $this->password;
    }
}
