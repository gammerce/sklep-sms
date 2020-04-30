<?php
namespace App\View\Pages\Shop;

use App\Repositories\UserRepository;
use App\Routing\UrlGenerator;
use App\Support\Template;
use App\Translation\TranslationManager;
use App\View\Interfaces\IBeLoggedCannot;
use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

class PageResetPassword extends Page implements IBeLoggedCannot
{
    const PAGE_ID = "reset_password";

    /** @var UserRepository */
    private $userRepository;

    /** @var UrlGenerator */
    private $url;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        UserRepository $userRepository,
        UrlGenerator $url
    ) {
        parent::__construct($template, $translationManager);

        $this->userRepository = $userRepository;
        $this->url = $url;
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("reset_password");
    }

    public function getContent(Request $request)
    {
        $resetKey = array_get($request->query->all(), "code");

        if (!strlen($resetKey)) {
            return $this->template->render("shop/pages/reset_password", [
                "content" => $this->lang->t("no_reset_key"),
            ]);
        }

        $user = $this->userRepository->findByResetKey($resetKey);
        if (!$user) {
            return $this->template->render("shop/pages/reset_password", [
                "content" => $this->lang->t("wrong_reset_key", $this->url->to("/page/contact")),
            ]);
        }

        $content = $this->template->render("shop/components/reset_password/form", [
            "code" => $resetKey,
        ]);

        return $this->template->render("shop/pages/reset_password", compact("content"));
    }
}
