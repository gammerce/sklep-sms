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
        assert(is_array($value));

        foreach ($value as $item) {
            $this->rule->validate($attribute, $item, $data);
        }
    }
}
