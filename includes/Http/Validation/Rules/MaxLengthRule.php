<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;

class MaxLengthRule extends BaseRule
{
    /** @var int */
    private $length;

    public function __construct($length)
    {
        parent::__construct();
        $this->length = $length;
    }

    public function validate($attribute, $value, array $data)
    {
        if (strlen($value) > $this->length) {
            return [$this->lang->t('max_length')];
        }

        return [];
    }
}
