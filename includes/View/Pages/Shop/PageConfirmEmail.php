<?php
namespace App\View\Pages\Shop;

use App\Repositories\UserRepository;
use App\Theme\Template;
use App\Translation\TranslationManager;
use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

class PageConfirmEmail extends Page
{
    const PAGE_ID = "confirm_email";

    private UserRepository $userRepository;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        UserRepository $userRepository
    ) {
        parent::__construct($template, $translationManager);
        $this->userRepository = $userRepository;
    }

    public function getTitle(?Request $request = null): string
    {
        return $this->lang->t("confirm_email");
    }

    public function getContent(Request $request)
    {
        $code = (string) array_get($request->query->all(), "code", "");
        $user = $this->userRepository->findByEmailChangeKey($code);

        if (!$user) {
            return $this->template->render("shop/pages/confirm_email", [
                "content" => $this->lang->t("email_change_wrong_token"),
            ]);
        }

        $newEmail = (string) $this->userRepository->getPendingEmail($user->getId());
        $addressLine = strlen($newEmail) ? $this->lang->t("confirm_email_address", $newEmail) : "";

        $content = $this->template->render("shop/components/confirm_email/form", [
            "code" => $code,
            "addressLine" => $addressLine,
        ]);

        return $this->template->render("shop/pages/confirm_email", compact("content"));
    }
}
