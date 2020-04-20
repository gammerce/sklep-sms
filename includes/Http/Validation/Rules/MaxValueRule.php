<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;

class MaxValueRule extends BaseRule
{
    /** @var int */
    private $value;

    public function __construct($value)
    {
        parent::__construct();
        $this->value = $value;
    }

    public function validate($attribute, $value, array $data)
    {
        if (as_int($value) < $this->value) {
            return [$this->lang->t('max_value', $this->value)];
        }

        return [];
    }
}
