<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;
use App\System\Heart;

class UserGroupsRule extends BaseRule
{
    /** @var Heart */
    private $heart;

    public function __construct()
    {
        parent::__construct();
        $this->heart = app()->make(Heart::class);
    }

    public function validate($attribute, $value, array $data)
    {
        if (!is_array($value)) {
            return ["Invalid type"];
        }

        foreach ($value as $gid) {
            if (!$this->heart->getGroup($gid)) {
                return [$this->lang->t('wrong_group')];
            }
        }

        return [];
    }
}
