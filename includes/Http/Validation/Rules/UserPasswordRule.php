<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;
use App\Models\User;

class UserPasswordRule extends BaseRule
{
    private User $user;

    public function __construct(User $user)
    {
        parent::__construct();
        $this->user = $user;
    }

    public function validate($attribute, $value, array $data): void
    {
        if (hash_password($value, $this->user->getSalt()) != $this->user->getPassword()) {
            throw new ValidationException($this->lang->t("old_pass_wrong"));
        }
    }
}
