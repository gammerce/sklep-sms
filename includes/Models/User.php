<?php
namespace App\Models;

use App\Payment\General\BillingAddress;
use App\Support\Money;
use App\User\Permission;

class User
{
    private ?int $id;
    private ?string $username;
    private ?string $password;
    private ?string $salt;
    private ?string $email;
    private ?string $forename;
    private ?string $surname;
    private ?string $steamId;
    private BillingAddress $billingAddress;

    /** @var int[] */
    private array $groups;

    private ?string $regDate;
    private ?string $lastActive;
    private Money $wallet;
    private ?string $regIp;
    private ?string $lastIp;
    private ?string $resetPasswordKey;

    /** @var Permission[] */
    private array $permissions;

    public function __construct(
        $id = null,
        $username = null,
        $password = null,
        $salt = null,
        $email = null,
        $forename = null,
        $surname = null,
        $steamId = null,
        BillingAddress $billingAddress = null,
        array $groups = [],
        $regDate = null,
        $lastActive = null,
        Money $wallet = null,
        $regIp = null,
        $lastIp = null,
        $resetPasswordKey = null,
        array $permissions = []
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->password = $password;
        $this->salt = $salt;
        $this->email = $email;
        $this->forename = $forename;
        $this->surname = $surname;
        $this->steamId = $steamId;
        $this->billingAddress = $billingAddress ?: BillingAddress::empty();
        $this->groups = $groups;
        $this->regDate = $regDate;
        $this->lastActive = $lastActive;
        $this->wallet = new Money($wallet);
        $this->regIp = $regIp;
        $this->lastIp = $lastIp;
        $this->resetPasswordKey = $resetPasswordKey;
        $this->permissions = collect($permissions)
            ->flatMap(fn(Permission $permission) => [$permission->getKey() => $permission])
            ->all();
    }

    public function exists(): bool
    {
        return !!$this->getId();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername($username): void
    {
        $this->username = $username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getSalt(): ?string
    {
        return $this->salt;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email): void
    {
        $this->email = $email;
    }

    public function getForename(): ?string
    {
        return $this->forename;
    }

    /**
     * @param string $forename
     */
    public function setForename($forename): void
    {
        $this->forename = $forename;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    /**
     * @param string $surname
     */
    public function setSurname($surname): void
    {
        $this->surname = $surname;
    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * @param int[] $groups
     */
    public function setGroups(array $groups): void
    {
        $this->groups = $groups;
    }

    public function getRegDate(): ?string
    {
        return $this->regDate;
    }

    public function getLastActive(): ?string
    {
        return $this->lastActive;
    }

    public function getWallet(): Money
    {
        return $this->wallet;
    }

    /**
     * @param Money|int $wallet
     */
    public function setWallet($wallet): void
    {
        $this->wallet = new Money($wallet);
    }

    public function getRegIp(): ?string
    {
        return $this->regIp;
    }

    public function getLastIp(): ?string
    {
        return $this->lastIp;
    }

    /**
     * @param string $lastIp
     */
    public function setLastIp($lastIp): void
    {
        $this->lastIp = $lastIp;
    }

    public function getResetPasswordKey(): ?string
    {
        return $this->resetPasswordKey;
    }

    /**
     * @param string $resetPasswordKey
     */
    public function setResetPasswordKey($resetPasswordKey): void
    {
        $this->resetPasswordKey = $resetPasswordKey;
    }

    public function can(Permission $permission): bool
    {
        return array_key_exists($permission->getKey(), $this->permissions);
    }

    public function cannot(Permission $permission): bool
    {
        return !$this->can($permission);
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function setPermissions(array $permissions): void
    {
        foreach ($permissions as $key => $value) {
            $this->permissions[$key] = $value;
        }
    }

    /**
     * Removes all permissions
     */
    public function removePermissions(): void
    {
        $this->permissions = [];
    }

    public function getSteamId(): ?string
    {
        return $this->steamId;
    }

    /**
     * @param string|null $steamId
     */
    public function setSteamId($steamId): void
    {
        $this->steamId = $steamId;
    }

    public function getBillingAddress(): BillingAddress
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(BillingAddress $billingAddress): void
    {
        $this->billingAddress = $billingAddress;
    }
}
