<?php
namespace App;

use App\Models\User;

class Auth
{
    /** @var Heart */
    private $heart;

    /** @var User */
    protected $user;

    public function __construct(Heart $heart)
    {
        $this->heart = $heart;
    }

    /**
     * @return User
     */
    public function user()
    {
        if ($this->user !== null) {
            return $this->user;
        }

        return $this->user = $this->heart->get_user();
    }

    public function check()
    {
        return $this->user !== null && $this->user->isLogged();
    }

    public function loginUserUsingId($uid)
    {
        $this->user = $this->heart->get_user($uid);
    }

    public function loginAdminUsingCredentials($username, $password)
    {
        $user = $this->heart->get_user(0, $username, $password);

        if ($user->isLogged() && get_privilages("acp")) {
            $_SESSION['uid'] = $user->getUid();
        } else {
            $_SESSION['info'] = "wrong_data";
        }

        $this->user = $user;
    }

    public function logoutAdmin()
    {
        // Unset all of the session variables.
        $_SESSION = [];

        // If it's desired to kill the session, also delete the session cookie.
        // Note: This will destroy the session, and not just the session data!
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // Finally, destroy the session.
        session_destroy();
    }
}