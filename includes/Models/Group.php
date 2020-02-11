<?php
namespace App\Models;

class Group
{
    /** @var int */
    private $id;

    /** @var string */
    private $name;

    /** @var array */
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
     * @return array
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * @param $permission
     * @return bool
     */
    public function hasPermission($permission)
    {
        return !!array_get($this->permissions, $permission);
    }
}
