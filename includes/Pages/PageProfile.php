<?php
namespace App\Pages;

use App\Interfaces\IBeLoggedMust;
use App\System\Auth;

class PageProfile extends Page implements IBeLoggedMust
{
    const PAGE_ID = 'profile';

    /** @var Auth */
    private $auth;

    public function __construct()
    {
        parent::__construct();
        $this->heart->pageTitle = $this->title = $this->lang->t('profile');
        $this->auth = $this->app->make(Auth::class);
    }

    protected function content(array $query, array $body)
    {
        $user = $this->auth->user();
        $email = $user->getEmail();
        $username = $user->getUsername();
        $forename = $user->getForename();
        $surname = $user->getSurname();
        $steamId = $user->getSteamId();

        return $this->template->render(
            "profile",
            compact("email", "username", "forename", "surname", "steamId")
        );
    }
}
