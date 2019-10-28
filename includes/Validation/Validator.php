<?php
namespace App\Validation;

use App\Exceptions\ValidationException;

class Validator
{
    /** @var array */
    private $data;

    /** @var array */
    private $rules;

    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
    }

    public function validate()
    {
        $result = [];

        foreach ($this->rules as $attribute => $rules) {
            /** @var Rule $rule */
            foreach ($rules as $rule) {
                $warnings = $rule->validate($attribute, array_get($this->data, $attribute), $this->data);

                if ($warnings) {
                    $result[$attribute] = array_merge(array_get($result, $attribute, []), $warnings);
                }
            }
        }

        return $result;
    }

    public function validateOrFail()
    {
        $warnings = $this->validate();

        if ($warnings) {
            throw new ValidationException($warnings);
        }
    }
}