<?php
namespace App\System;

use App\Managers\UserManager;
use App\Models\User;
use Symfony\Component\HttpFoundation\Request;
use UnexpectedValueException;

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
        $this->user = $this->userManager->getUser($userId);
    }

    /**
     * @param Request $request
     * @param User $user
     */
    public function loginUser(Request $request, User $user)
    {
        if (!$user->exists()) {
            throw new UnexpectedValueException("Given user is not logged in");
        }

        $this->userManager->setUser($user);
        $request->getSession()->set("uid", $user->getId());
        $this->user = $user;
    }

    /**
     * @param Request $request
     * @param User $user
     */
    public function loginAdmin(Request $request, User $user)
    {
        if (!$user->exists()) {
            throw new UnexpectedValueException("Given user is not logged in");
        }

        $session = $request->getSession();

        if ($user && has_privileges("acp", $user)) {
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
