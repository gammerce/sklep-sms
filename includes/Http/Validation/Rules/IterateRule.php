<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;
use App\Http\Validation\Rule;

class IterateRule extends BaseRule
{
    private Rule $rule;

    public function __construct(Rule $rule)
    {
        parent::__construct();

        $this->rule = $rule;
    }

    public function validate($attribute, $value, array $data)
    {
        if (!is_array($value)) {
            return [$this->lang->t("field_array")];
        }

        foreach ($value as $item) {
            $result = $this->rule->validate($attribute, $item, $data);
            if (count($result)) {
                return $result;
            }
        }

        return [];
    }
}
