<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;
use App\Repositories\UserRepository;

class UniqueUserEmailRule extends BaseRule
{
    /** @var UserRepository */
    private $userRepository;

    /** @var int|null */
    private $exceptUserId;

    public function __construct($exceptUserId = null)
    {
        parent::__construct();
        $this->userRepository = app()->make(UserRepository::class);
        $this->exceptUserId = $exceptUserId;
    }

    public function validate($attribute, $value, array $data)
    {
        $user = $this->userRepository->findByEmail($value);

        if ($user && $user->getId() !== $this->exceptUserId) {
            return [$this->lang->t("email_occupied")];
        }

        return [];
    }
}
