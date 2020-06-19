<?php
namespace App\Models;

use App\User\Permission;

class Group
{
    /** @var int */
    private $id;

    /** @var string */
    private $name;

    /** @var Permission[] */
    private $permissions;

    public function __construct($id, $name, array $permissions)
    {
        $this->id = $id;
        $this->name = $name;
        $this->permissions = $permissions;
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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Permission[]
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * @param Permission $permission
     * @return bool
     */
    public function hasPermission(Permission $permission)
    {
        foreach ($this->permissions as $lookup) {
            if ($lookup->equals($permission)) {
                return true;
            }
        }

        return false;
    }
}
