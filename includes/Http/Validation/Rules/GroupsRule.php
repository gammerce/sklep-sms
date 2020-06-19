<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;
use App\Managers\GroupManager;

class GroupsRule extends BaseRule
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

        foreach ($value as $groupId) {
            if (!$this->groupManager->get($groupId)) {
                return [$this->lang->t('wrong_group')];
            }
        }

        return [];
    }
}
