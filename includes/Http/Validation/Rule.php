<?php
namespace App\Http\Validation;

interface Rule
{
    /**
     * @param string $attribute
     * @param mixed  $value
     * @param array  $data
     * @return array
     */
    public function validate($attribute, $value, array $data);
}
