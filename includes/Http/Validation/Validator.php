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
            foreach ($rules as $rule) {
                $value = array_get($this->data, $attribute);

                if ($rule instanceof Rule && ($rule instanceof EmptyRule || has_value($value))) {
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

    public function extendRules(array $rules)
    {
        $this->rules = array_merge_recursive($this->rules, $rules);
    }

    public function extendData(array $data)
    {
        $this->data = array_merge($this->data, $data);
    }

    public function getData($attribute)
    {
        return array_get($this->data, $attribute);
    }
}
