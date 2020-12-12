<?php
namespace App\System;

use App\Managers\UserManager;
use App\Models\User;
use App\User\Permission;
use Symfony\Component\HttpFoundation\Request;

class Auth
{
    /** @var UserManager */
    private $userManager;

    /** @var User */
    private $user;

    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * @return User
     */
    public function user()
    {
        if ($this->user === null) {
            $this->user = new User();
        }

        return $this->user;
    }

    public function check()
    {
        return $this->user !== null && $this->user->exists();
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    public function setUserById($userId)
    {
        $this->user = $this->userManager->get($userId);
    }

    /**
     * @param Request $request
     * @param User $user
     */
    public function loginUser(Request $request, User $user)
    {
        assert($user->exists());

        $this->userManager->set($user);
        $request->getSession()->set("uid", $user->getId());
        $this->user = $user;
    }

    /**
     * @param Request $request
     * @param User|null $user
     */
    public function loginAdmin(Request $request, User $user = null)
    {
        $session = $request->getSession();

        if ($user && $user->can(Permission::ACP())) {
            $session->set("uid", $user->getId());
            $this->user = $user;
        } else {
            $session->set("info", "wrong_data");
        }
    }

    public function logout(Request $request)
    {
        $this->user = null;
        $request->getSession()->invalidate();
    }
}
