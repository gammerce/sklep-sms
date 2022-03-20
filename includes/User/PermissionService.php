<?php
namespace App\User;

use App\Models\Group;
use App\Models\User;

class PermissionService
{
    public function canUserAssignGroup(User $user, Group $group): bool
    {
        if (!$user->can(Permission::USERS_MANAGEMENT())) {
            return false;
        }
        return is_subset($group->getPermissions(), $user->getPermissions());
    }

    public function canChangeUserGroup(User $actor, User $target): bool
    {
        return is_subset($target->getPermissions(), $actor->getPermissions());
    }
}
