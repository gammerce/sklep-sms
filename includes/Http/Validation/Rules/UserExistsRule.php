<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;
use App\Managers\UserManager;

class UserExistsRule extends BaseRule
{
    private UserManager $userManager;

    public function __construct()
    {
        parent::__construct();
        $this->userManager = app()->make(UserManager::class);
    }

    public function validate($attribute, $value, array $data): void
    {
        if (!$this->userManager->get($value)->exists()) {
            throw new ValidationException($this->lang->t("no_account_id"));
        }
    }
}
