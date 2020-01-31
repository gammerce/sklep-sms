<?php
namespace App\ServiceModules\ExtraFlags;

class PlayerFlag
{
    const FLAGS = [
        "a",
        "b",
        "c",
        "d",
        "e",
        "f",
        "g",
        "h",
        "i",
        "j",
        "k",
        "l",
        "m",
        "n",
        "o",
        "p",
        "q",
        "r",
        "s",
        "t",
        "u",
        "y",
        "v",
        "w",
        "x",
        "z",
    ];

    /** @var int */
    private $id;

    /** @var int */
    private $server;

    /** @var int */
    private $type;

    /** @var string */
    private $authData;

    /** @var string */
    private $password;
    /** @var array */
    private $flags;

    public function __construct($id, $server, $type, $authData, $password, array $flags)
    {
        $this->id = $id;
        $this->server = $server;
        $this->type = $type;
        $this->authData = $authData;
        $this->password = $password;
        $this->flags = $flags;
    }

    /**
     * @return int
     */
    public function getServerId()
    {
        return $this->server;
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

    /**
     * @return array
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * @param string $flag
     * @return int|null
     */
    public function getFlag($flag)
    {
        return array_get($this->flags, $flag);
    }
}
