<?php
namespace App\Http\Validation;

use App\Exceptions\ValidationException;

interface Rule
{
    /**
     * @param string $attribute
     * @param mixed  $value
     * @param array  $data
     * @return void
     * @throws ValidationException
     */
    public function validate($attribute, $value, array $data): void;
    public function breaksPipelineOnWarning(): bool;
    public function acceptsEmptyValue(): bool;
}
