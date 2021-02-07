<?php
namespace App\Http\Validation;

use App\Exceptions\ValidationException;

class Validator
{
    private array $data;
    private array $rules;

    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
    }

    public function validate(): WarningBag
    {
        $warnings = new WarningBag();

        foreach ($this->rules as $attribute => $rules) {
            foreach ($rules as $rule) {
                if (!($rule instanceof Rule)) {
                    continue;
                }

                $value = array_get($this->data, $attribute);

                if ($rule->acceptsEmptyValue() || has_value($value)) {
                    try {
                        $rule->validate($attribute, $value, $this->data);
                    } catch (ValidationException $e) {
                        $warnings->add($attribute, $e->warnings);

                        if ($rule->breaksPipelineOnWarning()) {
                            break;
                        }
                    }
                }
            }
        }

        return $warnings;
    }

    public function validateOrFail(): array
    {
        $warnings = $this->validate();

        if ($warnings->isPopulated()) {
            throw new ValidationException($warnings);
        }

        return $this->validated();
    }

    public function validated(): array
    {
        return collect(array_keys($this->rules))
            ->flatMap(
                fn($attribute) => [
                    $attribute => array_get($this->data, $attribute),
                ]
            )
            ->all();
    }

    public function extendRules(array $rules): void
    {
        $this->rules = array_merge_recursive($this->rules, $rules);
    }

    public function extendData(array $data): void
    {
        $this->data = array_merge($this->data, $data);
    }

    public function getData($attribute)
    {
        return array_get($this->data, $attribute);
    }
}
