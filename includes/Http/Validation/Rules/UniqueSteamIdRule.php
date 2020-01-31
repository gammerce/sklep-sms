<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;
use App\Repositories\UserRepository;

class UniqueSteamIdRule extends BaseRule
{
    /** @var UserRepository */
    private $userRepository;

    /** @var int */
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

        if ($user && $user->getUid() !== $this->exceptUserId) {
            return [$this->lang->t("steam_id_occupied")];
        }

        return [];
    }
}
