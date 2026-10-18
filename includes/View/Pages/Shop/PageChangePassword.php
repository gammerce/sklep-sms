<?php
namespace App\View\Pages\Shop;

use App\System\Auth;
use App\Theme\Template;
use App\Translation\TranslationManager;
use App\View\Interfaces\IBeLoggedMust;
use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

class PageChangePassword extends Page implements IBeLoggedMust
{
    const PAGE_ID = "change_password";

    private Auth $auth;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        Auth $auth
    ) {
        parent::__construct($template, $translationManager);
        $this->auth = $auth;
    }

    public function getTitle(?Request $request = null): string
    {
        return $this->lang->t(
            $this->auth->user()->hasPassword() ? "change_password" : "set_password"
        );
    }

    public function getContent(Request $request)
    {
        // Steam-only accounts have no password yet: there is no old password to
        // prove, so the field is hidden and a different copy is shown.
        $hasPassword = $this->auth->user()->hasPassword();
        $title = $this->lang->t($hasPassword ? "change_password" : "set_password");
        $subtitle = $this->lang->t(
            $hasPassword ? "change_password_subtitle" : "set_password_subtitle"
        );

        return $this->template->render(
            "shop/pages/change_password",
            compact("hasPassword", "title", "subtitle")
        );
    }
}
