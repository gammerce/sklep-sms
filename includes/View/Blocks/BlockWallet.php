<?php
namespace App\View\Blocks;

use App\Managers\ServiceManager;
use App\Routing\UrlGenerator;
use App\Service\UserServiceAccessService;
use App\Theme\Template;
use App\System\Auth;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\View\Interfaces\IBeLoggedMust;
use Symfony\Component\HttpFoundation\Request;

class BlockWallet extends Block implements IBeLoggedMust
{
    const BLOCK_ID = "wallet";

    private Auth $auth;
    private Template $template;
    private UrlGenerator $url;
    private UserServiceAccessService $userServiceAccessService;
    private ServiceManager $serviceManager;
    private Translator $lang;

    public function __construct(
        Auth $auth,
        Template $template,
        UrlGenerator $url,
        UserServiceAccessService $userServiceAccessService,
        ServiceManager $serviceManager,
        TranslationManager $translationManager
    ) {
        $this->auth = $auth;
        $this->template = $template;
        $this->url = $url;
        $this->userServiceAccessService = $userServiceAccessService;
        $this->serviceManager = $serviceManager;
        $this->lang = $translationManager->user();
    }

    public function getContentClass(): string
    {
        return "wallet-status";
    }

    public function getContent(Request $request, array $params): string
    {
        $user = $this->auth->user();
        $balance = $user->getWallet();
        $service = $this->serviceManager->get("charge_wallet");

        if ($service && $this->userServiceAccessService->canUserUseService($service, $user)) {
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

        return $this->template->render(
            "shop/layout/wallet",
            compact("chargeWalletButton", "balance")
        );
    }
}
