<?php
namespace App\View\Blocks;

use App\Managers\ServiceManager;
use App\Routing\UrlGenerator;
use App\Services\UserServiceAccessService;
use App\Support\Template;
use App\System\Auth;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\User\Permission;
use Symfony\Component\HttpFoundation\Request;

class BlockUserButtons extends Block
{
    const BLOCK_ID = "user_buttons";

    /** @var Auth */
    private $auth;

    /** @var Template */
    private $template;

    /** @var ServiceManager */
    private $serviceManager;

    /** @var UrlGenerator */
    private $url;

    /** @var Translator */
    private $lang;

    /** @var UserServiceAccessService */
    private $userServiceAccessService;

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

    public function getContentClass()
    {
        return $this->auth->check() ? "user-buttons" : "loginarea";
    }

    public function getContent(Request $request, array $params)
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

        // TODO Remove along with retro theme
        if (
            $this->userServiceAccessService->canUserUseService(
                $this->serviceManager->getService("charge_wallet"),
                $user
            )
        ) {
            $chargeWalletButton = $this->template->render(
                "shop/components/navbar/navigation_item_icon",
                [
                    "icon" => "fa-wallet",
                    "link" => $this->url->to("/page/purchase", ["service" => "charge_wallet"]),
                    "text" => $this->lang->t("charge_wallet"),
                ]
            );
        } else {
            $chargeWalletButton = "";
        }

        return $this->template->render("shop/layout/user_buttons", [
            "acpButton" => $acpButton,
            "chargeWalletButton" => $chargeWalletButton,
            "username" => $user->getUsername(),
            "userId" => $user->getId(),
        ]);
    }
}
