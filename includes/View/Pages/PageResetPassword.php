<?php
namespace App\View\Pages;

use App\Repositories\UserRepository;
use App\Support\Template;
use App\Translation\TranslationManager;
use App\View\Interfaces\IBeLoggedCannot;
use Symfony\Component\HttpFoundation\Request;

class PageResetPassword extends Page implements IBeLoggedCannot
{
    const PAGE_ID = "reset_password";

    /** @var UserRepository */
    private $userRepository;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        UserRepository $userRepository
    ) {
        parent::__construct($template, $translationManager);

        $this->userRepository = $userRepository;
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("reset_password");
    }

    public function getContent(Request $request)
    {
        $resetKey = array_get($request->query->all(), "code");

        if (!strlen($resetKey)) {
            return $this->lang->t("no_reset_key");
        }

        $user = $this->userRepository->findByResetKey($resetKey);
        if (!$user) {
            return $this->lang->t("wrong_reset_key");
        }

        return $this->template->render("reset_password", ["code" => $resetKey]);
    }
}
