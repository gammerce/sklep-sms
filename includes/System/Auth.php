<?php
namespace App\System;

use App\Models\User;
use Symfony\Component\HttpFoundation\Request;

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

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    public function check()
    {
        return $this->user !== null && $this->user->exists();
    }

    public function loginUserUsingId($uid)
    {
        $this->user = $this->heart->getUser($uid);
    }

    public function loginAdminUsingCredentials($username, $password)
    {
        $user = $this->heart->getUserByLogin($username, $password);

        if ($user->exists() && get_privileges("acp", $user)) {
            $this->getSession()->set("uid", $user->getUid());
        } else {
            $this->getSession()->set("info", "wrong_data");
        }

        $this->user = $user;
    }

    public function logoutAdmin()
    {
        $this->getSession()->invalidate();
    }

    private function getSession()
    {
        /** @var Request $request */
        $request = app()->make(Request::class);
        return $request->getSession();
    }
}
