<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;

class InArrayRule extends BaseRule
{
    private array $values;

    public function __construct(array $values)
    {
        parent::__construct();
        $this->values = $values;
    }

    public function validate($attribute, $value, array $data): void
    {
        if (!in_array($value, $this->values, true)) {
            throw new ValidationException("Invalid value");
        }
    }
}
