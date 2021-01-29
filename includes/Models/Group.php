<?php
namespace App\Models;

use App\User\Permission;

class Group
{
    private int $id;
    private string $name;

    /** @var Permission[] */
    private array $permissions;

    public function __construct($id, $name, array $permissions)
    {
        $this->id = $id;
        $this->name = $name;
        $this->permissions = $permissions;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Permission[]
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function hasPermission(Permission $permission): bool
    {
        foreach ($this->permissions as $lookup) {
            if ($lookup->equals($permission)) {
                return true;
            }
        }

        return false;
    }
}
