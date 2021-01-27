<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;
use App\Repositories\UserRepository;

class UniqueSteamIdRule extends BaseRule
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
        $user = $this->userRepository->findBySteamId($value);

        if ($user && $user->getId() !== $this->exceptUserId) {
            throw new ValidationException($this->lang->t("steam_id_occupied"));
        }
    }
}
