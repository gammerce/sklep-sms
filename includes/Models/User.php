<?php
namespace App\Models;

use App\User\Permission;
use Symfony\Component\HttpFoundation\Request;

class User
{
    /** @var int */
    private $id;

    /** @var string */
    private $username;

    /** @var string */
    private $password;

    /** @var string */
    private $salt;

    /** @var string */
    private $email;

    /** @var string */
    private $forename;

    /** @var string */
    private $surname;

    /** @var string|null */
    private $steamId;

    /** @var int[] */
    private $groups;

    /** @var string */
    private $regDate;

    /** @var string */
    private $lastActive;

    /** @var int */
    private $wallet;

    /** @var string */
    private $regIp;

    /** @var string */
    private $lastIp;

    /** @var string */
    private $resetPasswordKey;

    /** @var Permission[] */
    private $permissions;

    /** @var string */
    private $platform;

    public function __construct(
        $id = null,
        $username = null,
        $password = null,
        $salt = null,
        $email = null,
        $forename = null,
        $surname = null,
        $steamId = null,
        $groups = [],
        $regDate = null,
        $lastActive = null,
        $wallet = null,
        $regIp = null,
        $lastIp = null,
        $resetPasswordKey = null,
        array $permissions = []
    ) {
        /** @var Request $request */
        $request = app()->make(Request::class);

        $this->id = $id;
        $this->username = $username;
        $this->password = $password;
        $this->salt = $salt;
        $this->email = $email;
        $this->forename = $forename;
        $this->surname = $surname;
        $this->steamId = $steamId;
        $this->groups = $groups;
        $this->regDate = $regDate;
        $this->lastActive = $lastActive;
        $this->wallet = $wallet;
        $this->regIp = $regIp;
        $this->lastIp = $lastIp ?: get_ip();
        $this->resetPasswordKey = $resetPasswordKey;
        $this->platform = $request->headers->get("User-Agent", "");
        $this->permissions = collect($permissions)
            ->flatMap(function (Permission $permission) {
                return [$permission->getKey() => $permission];
            })
            ->all();
    }

    public function exists()
    {
        return !!$this->getId();
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
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getForename()
    {
        return $this->forename;
    }

    /**
     * @param string $forename
     */
    public function setForename($forename)
    {
        $this->forename = $forename;
    }

    /**
     * @return string
     */
    public function getSurname()
    {
        return $this->surname;
    }

    /**
     * @param string $surname
     */
    public function setSurname($surname)
    {
        $this->surname = $surname;
    }

    /**
     * @return array
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @param array $groups
     */
    public function setGroups(array $groups)
    {
        $this->groups = $groups;
    }

    /**
     * @return string
     */
    public function getRegDate()
    {
        return $this->regDate;
    }

    /**
     * @return string
     */
    public function getLastActive()
    {
        return $this->lastActive;
    }

    /**
     * @return int
     */
    public function getWallet()
    {
        return $this->wallet;
    }

    /**
     * @param int $wallet
     */
    public function setWallet($wallet)
    {
        $this->wallet = $wallet;
    }

    /**
     * @return string
     */
    public function getRegIp()
    {
        return $this->regIp;
    }

    /**
     * @return string
     */
    public function getLastIp()
    {
        return $this->lastIp;
    }

    /**
     * @param string $lastIp
     */
    public function setLastIp($lastIp)
    {
        $this->lastIp = $lastIp;
    }

    /**
     * @return string
     */
    public function getResetPasswordKey()
    {
        return $this->resetPasswordKey;
    }

    /**
     * @param string $resetPasswordKey
     */
    public function setResetPasswordKey($resetPasswordKey)
    {
        $this->resetPasswordKey = $resetPasswordKey;
    }

    /**
     * @param Permission $permission
     * @return boolean
     */
    public function can(Permission $permission)
    {
        return array_key_exists($permission->getKey(), $this->permissions);
    }

    /**
     * @param Permission $permission
     * @return bool
     */
    public function cannot(Permission $permission)
    {
        return !$this->can($permission);
    }

    /**
     * @return array
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * @param array $permissions
     */
    public function setPermissions($permissions)
    {
        foreach ($permissions as $key => $value) {
            $this->permissions[$key] = $value;
        }
    }

    /**
     * Removes all permissions
     */
    public function removePermissions()
    {
        $this->permissions = [];
    }

    /**
     * @return string
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * @param string $platform
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;
    }

    /**
     * @return string|null
     */
    public function getSteamId()
    {
        return $this->steamId;
    }

    /**
     * @param string|null $steamId
     */
    public function setSteamId($steamId)
    {
        $this->steamId = $steamId;
    }
}
