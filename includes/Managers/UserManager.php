<?php
namespace App\Managers;

use App\Models\User;
use App\Repositories\UserRepository;

class UserManager
{
    private UserRepository $userRepository;

    /** @var User[] */
    private array $users = [];

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @param int $userId
     * @return User
     */
    public function get($userId): User
    {
        if ($userId && isset($this->users[$userId])) {
            return $this->users[$userId];
        }

        $user = $this->userRepository->get($userId);

        if ($user) {
            $this->users[$user->getId()] = $user;
            return $user;
        }

        return new User();
    }

    public function set(User $user): void
    {
        $this->users[$user->getId()] = $user;
    }

    /**
     * @param string $login
     * @param string $password
     * @return User|null
     */
    public function getByLogin($login, $password): ?User
    {
        $user = $this->userRepository->findByPassword($login, $password);

        if ($user) {
            $this->users[$user->getId()] = $user;
            return $user;
        }

        return null;
    }
}
