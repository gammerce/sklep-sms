<?php
namespace App\System;

use App\Managers\UserManager;
use App\Models\User;
use App\User\Permission;
use Symfony\Component\HttpFoundation\Request;

class Auth
{
    private UserManager $userManager;
    private ?User $user = null;

    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    public function user(): User
    {
        if ($this->user === null) {
            $this->user = new User();
        }

        return $this->user;
    }

    public function check(): bool
    {
        return $this->user !== null && $this->user->exists();
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function setUserById($userId): void
    {
        $this->user = $this->userManager->get($userId);
    }

    public function loginUser(Request $request, User $user): void
    {
        assert($user->exists());

        $this->userManager->set($user);
        $request->getSession()->set("uid", $user->getId());
        $this->user = $user;
    }

    public function loginAdmin(Request $request, User $user = null): void
    {
        $session = $request->getSession();

        if ($user && $user->can(Permission::ACP())) {
            $session->set("uid", $user->getId());
            $this->user = $user;
        } else {
            $session->set("info", "wrong_data");
        }
    }

    public function logout(Request $request): void
    {
        $this->user = null;
        $request->getSession()->invalidate();
    }
}
