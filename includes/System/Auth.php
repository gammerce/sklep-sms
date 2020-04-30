<?php
namespace App\System;

use App\Models\User;
use Symfony\Component\HttpFoundation\Request;
use UnexpectedValueException;

class Auth
{
    /** @var Heart */
    private $heart;

    /** @var User */
    private $user;

    public function __construct(Heart $heart)
    {
        $this->heart = $heart;
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

    public function setUserById($uid)
    {
        $this->user = $this->heart->getUser($uid);
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

        $this->heart->setUser($user);
        $request->getSession()->set("uid", $user->getUid());
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
            $session->set("uid", $user->getUid());
            $this->user = $user;
        } else {
            $session->set("info", "wrong_data");
        }
    }

    public function logout(Request $request)
    {
        $request->getSession()->invalidate();
    }
}
