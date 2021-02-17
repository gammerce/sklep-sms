<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;
use App\Managers\GroupManager;
use App\System\Auth;
use App\User\PermissionService;

class UserGroupsRule extends BaseRule
{
    private GroupManager $groupManager;
    private Auth $auth;
    private PermissionService $permissionService;

    public function __construct()
    {
        parent::__construct();
        $this->groupManager = app()->make(GroupManager::class);
        $this->auth = app()->make(Auth::class);
        $this->permissionService = app()->make(PermissionService::class);
    }

    public function validate($attribute, $value, array $data): void
    {
        assert(is_array($value));

        foreach ($value as $groupId) {
            $group = $this->groupManager->get($groupId);
            if (!$group) {
                throw new ValidationException($this->lang->t("wrong_group"));
            }

            if (!$this->permissionService->canUserAssignGroup($this->auth->user(), $group)) {
                throw new ValidationException($this->lang->t("wrong_group"));
            }
        }
    }
}
