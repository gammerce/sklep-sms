<?php
namespace App\User;

use App\Models\Group;
use App\Models\User;

class GroupService
{
    public function canUserAssignGroup(User $user, Group $group): bool
    {
        if (!$user->can(Permission::MANAGE_USERS())) {
            return false;
        }

        $commonPermissions = array_intersect($user->getPermissions(), $group->getPermissions());

        return count($commonPermissions) === count($group->getPermissions());
    }
}
