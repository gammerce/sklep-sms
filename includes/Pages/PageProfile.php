<?php
namespace App\Pages;

use App\System\Auth;
use App\Interfaces\IBeLoggedMust;

class PageProfile extends Page implements IBeLoggedMust
{
    const PAGE_ID = 'profile';

    /** @var Auth */
    private $auth;

    public function __construct()
    {
        parent::__construct();
        $this->heart->pageTitle = $this->title = $this->lang->translate('profile');
        $this->auth = $this->app->make(Auth::class);
    }

    protected function content(array $query, array $body)
    {
        $user = $this->auth->user();
        $email = htmlspecialchars($user->getEmail(false));
        $username = htmlspecialchars($user->getUsername(false));
        $forename = htmlspecialchars($user->getForename());
        $surname = htmlspecialchars($user->getSurname());
        $steamId = htmlspecialchars($user->getSteamId());

        return $this->template->render(
            "profile",
            compact("email", "username", "forename", "surname", "steamId")
        );
    }
}
