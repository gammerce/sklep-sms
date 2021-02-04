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

    private int $id;
    private int $server;
    private int $type;
    private string $authData;
    private string $password;
    private array $flags;

    public function __construct($id, $server, $type, $authData, $password, array $flags)
    {
        $this->id = $id;
        $this->server = $server;
        $this->type = $type;
        $this->authData = $authData;
        $this->password = $password;
        $this->flags = $flags;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getServerId(): int
    {
        return $this->server;
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

    public function getFlags(): array
    {
        return $this->flags;
    }

    /**
     * @param string $flag
     * @return int|null
     */
    public function getFlag($flag): ?int
    {
        return array_get($this->flags, $flag);
    }
}
