<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\Rule;

class UsernameRule implements Rule
{
    public function validate($attribute, $value, array $data)
    {
        return check_for_warnings("username", $value);
    }
}