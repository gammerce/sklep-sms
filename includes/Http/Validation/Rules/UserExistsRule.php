<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;
use App\Repositories\UserRepository;

class UserExistsRule extends BaseRule
{
    /** @var UserRepository */
    private $userRepository;

    public function __construct()
    {
        parent::__construct();
        $this->userRepository = app()->make(UserRepository::class);
    }

    public function validate($attribute, $value, array $data)
    {
        if (!$this->userRepository->get($value)) {
            throw new ValidationException($this->lang->t("no_account_id"));
        }
    }
}
