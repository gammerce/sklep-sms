<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;
use App\Managers\GroupManager;

class UserGroupsRule extends BaseRule
{
    /** @var GroupManager */
    private $groupManager;

    public function __construct()
    {
        parent::__construct();
        $this->groupManager = app()->make(GroupManager::class);
    }

    public function validate($attribute, $value, array $data)
    {
        if (!is_array($value)) {
            return ["Invalid type"];
        }

        foreach ($value as $gid) {
            if (!$this->groupManager->get($gid)) {
                return [$this->lang->t("wrong_group")];
            }
        }

        return [];
    }
}
