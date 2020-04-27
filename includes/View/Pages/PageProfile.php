<?php
namespace App\View\Pages;

use App\Support\Template;
use App\System\Auth;
use App\Translation\TranslationManager;
use App\View\Interfaces\IBeLoggedMust;
use Symfony\Component\HttpFoundation\Request;

class PageProfile extends Page implements IBeLoggedMust
{
    const PAGE_ID = 'profile';

    /** @var Auth */
    private $auth;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        Auth $auth
    ) {
        parent::__construct($template, $translationManager);
        $this->auth = $auth;
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t('profile');
    }

    public function getContent(array $query, array $body)
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
