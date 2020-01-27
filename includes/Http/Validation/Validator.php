<?php
namespace App\Http\Validation;

use App\Exceptions\ValidationException;
use App\Http\Validation\Rules\RequiredRule;

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
        $warnings = new WarningBag();

        foreach ($this->rules as $attribute => $rules) {
            /** @var Rule $rule */
            foreach ($rules as $rule) {
                $value = array_get($this->data, $attribute);

                if ($rule instanceof RequiredRule || strlen($value)) {
                    $result = $rule->validate($attribute, $value, $this->data);

                    if ($result) {
                        $warnings->add($attribute, $result);
                    }
                }
            }
        }

        return $warnings;
    }

    public function validateOrFail()
    {
        $warnings = $this->validate();

        if ($warnings->isPopulated()) {
            throw new ValidationException($warnings);
        }

        return $this->validated();
    }

    public function validateOrFailWith(array $data)
    {
        $warnings = $this->validate();

        if ($warnings->isPopulated()) {
            throw new ValidationException($warnings, $data);
        }

        return $this->validated();
    }

    public function validated()
    {
        return collect(array_keys($this->rules))
            ->flatMap(function ($attribute) {
                return [
                    $attribute => array_get($this->data, $attribute),
                ];
            })
            ->all();
    }
}
