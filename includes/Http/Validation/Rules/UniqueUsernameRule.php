<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;
use App\Repositories\UserRepository;

class UniqueUsernameRule extends BaseRule
{
    private UserRepository $userRepository;
    private ?int $exceptUserId;

    public function __construct($exceptUserId = null)
    {
        parent::__construct();
        $this->userRepository = app()->make(UserRepository::class);
        $this->exceptUserId = $exceptUserId;
    }

    public function validate($attribute, $value, array $data): void
    {
        $user = $this->userRepository->findByUsername($value);

        if ($user && $user->getId() !== $this->exceptUserId) {
            throw new ValidationException($this->lang->t("nick_occupied"));
        }
    }
}
