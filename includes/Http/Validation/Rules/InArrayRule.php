<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;

class InArrayRule extends BaseRule
{
    /** @var array */
    private $values;

    public function __construct(array $values)
    {
        parent::__construct();
        $this->values = $values;
    }

    public function validate($attribute, $value, array $data)
    {
        if (!in_array($value, $this->values, true)) {
            return ["Invalid value"];
        }

        return [];
    }
}
