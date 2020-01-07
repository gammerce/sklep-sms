<?php
namespace App\Models;

use App\System\Heart;
use Symfony\Component\HttpFoundation\Request;

class User
{
    /** @var integer */
    private $uid;

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

    /** @var string */
    private $steamId;

    /** @var array */
    private $groups = [];

    /** @var string */
    private $regDate;

    /** @var string */
    private $lastActive;

    /** @var integer */
    private $wallet;

    /** @var string */
    private $regIp;

    /** @var string */
    private $lastIp;

    /** @var string */
    private $resetPasswordKey;

    /** @var array */
    private $privileges = [];

    /** @var string */
    private $platform;

    public function __construct(
        $uid = null,
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
        $resetPasswordKey = null
    ) {
        /** @var Request $request */
        $request = app()->make(Request::class);

        /** @var Heart $heart */
        $heart = app()->make(Heart::class);

        $this->uid = $uid;
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
        $this->platform = $request->headers->get('User-Agent');

        if ($this->groups) {
            foreach ($this->groups as $groupId) {
                $privileges = $heart->getGroupPrivileges($groupId);
                foreach ($privileges as $privilege => $value) {
                    if (strlen($privilege)) {
                        $this->privileges[$privilege] = $value ? true : false;
                    }
                }
            }
        }
    }

    public function exists()
    {
        return $this->getUid() ? true : false;
    }

    /**
     * @return int
     */
    public function getUid()
    {
        return $this->uid;
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
     * @param bool $divide
     *
     * @return int
     */
    public function getWallet($divide = false)
    {
        return $divide ? number_format($this->wallet / 100.0, 2) : $this->wallet;
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
     * @param string $key
     *
     * @return boolean
     */
    public function hasPrivilege($key)
    {
        return array_get($this->privileges, $key, false);
    }

    /**
     * @return array
     */
    public function getPrivileges()
    {
        return $this->privileges;
    }

    /**
     * @param array $privileges
     */
    public function setPrivileges($privileges)
    {
        foreach ($privileges as $key => $value) {
            $this->privileges[$key] = $value;
        }
    }

    /**
     * Removes all privileges
     */
    public function removePrivileges()
    {
        $this->privileges = [];
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
     * @return string
     */
    public function getSteamId()
    {
        return $this->steamId;
    }

    /**
     * @param string $steamId
     */
    public function setSteamId($steamId)
    {
        $this->steamId = $steamId;
    }
}
