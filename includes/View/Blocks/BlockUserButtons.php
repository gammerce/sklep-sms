<?php
namespace App\View\Blocks;

use App\Managers\ServiceManager;
use App\Routing\UrlGenerator;
use App\Service\UserServiceAccessService;
use App\Theme\Template;
use App\System\Auth;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\User\Permission;
use Symfony\Component\HttpFoundation\Request;

class BlockUserButtons extends Block
{
    const BLOCK_ID = "user_buttons";

    private Auth $auth;
    private Template $template;
    private ServiceManager $serviceManager;
    private UrlGenerator $url;
    private Translator $lang;
    private UserServiceAccessService $userServiceAccessService;

    public function __construct(
        Auth $auth,
        Template $template,
        TranslationManager $translationManager,
        ServiceManager $serviceManager,
        UrlGenerator $url,
        UserServiceAccessService $userServiceAccessService
    ) {
        $this->auth = $auth;
        $this->template = $template;
        $this->serviceManager = $serviceManager;
        $this->url = $url;
        $this->lang = $translationManager->user();
        $this->userServiceAccessService = $userServiceAccessService;
    }

    public function getContentClass(): string
    {
        return $this->auth->check() ? "user-buttons" : "loginarea";
    }

    public function getContent(Request $request, array $params): string
    {
        if (!$this->auth->check()) {
            return $this->template->render("shop/layout/loginarea");
        }

        $user = $this->auth->user();

        if ($user->can(Permission::ACP())) {
            $acpButton = $this->template->render("shop/components/navbar/navigation_item_icon", [
                "icon" => "fa-user-shield",
                "link" => $this->url->to("/admin"),
                "text" => $this->lang->t("acp"),
            ]);
        } else {
            $acpButton = "";
        }

        return $this->template->render("shop/layout/user_buttons", [
            "acpButton" => $acpButton,
            "username" => $user->getUsername(),
            "userId" => $user->getId(),
        ]);
    }
}
