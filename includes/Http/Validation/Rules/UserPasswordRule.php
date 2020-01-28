<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;
use App\Models\User;

class UserPasswordRule extends BaseRule
{
    /** @var User */
    private $user;

    public function __construct(User $user)
    {
        parent::__construct();
        $this->user = $user;
    }

    public function validate($attribute, $value, array $data)
    {
        if (hash_password($value, $this->user->getSalt()) != $this->user->getPassword()) {
            return [$this->lang->t('old_pass_wrong')];
        }

        return [];
    }
}
