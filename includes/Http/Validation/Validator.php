<?php
namespace App\Http\Validation;

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
        $warnings = new WarningBag();

        foreach ($this->rules as $attribute => $rules) {
            /** @var Rule $rule */
            foreach ($rules as $rule) {
                $result = $rule->validate(
                    $attribute,
                    array_get($this->data, $attribute),
                    $this->data
                );

                if ($result) {
                    $warnings->add($attribute, $result);
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
