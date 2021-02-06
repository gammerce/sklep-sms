<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;
use App\Managers\GroupManager;

class GroupsRule extends BaseRule
{
    private GroupManager $groupManager;

    public function __construct()
    {
        parent::__construct();
        $this->groupManager = app()->make(GroupManager::class);
    }

    public function validate($attribute, $value, array $data): void
    {
        assert(is_array($value));

        foreach ($value as $groupId) {
            if (!$this->groupManager->get($groupId)) {
                throw new ValidationException($this->lang->t("wrong_group"));
            }
        }
    }
}
