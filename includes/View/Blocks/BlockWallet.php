<?php
namespace App\View\Blocks;

use App\Managers\ServiceManager;
use App\Routing\UrlGenerator;
use App\Services\PriceTextService;
use App\Services\UserServiceAccessService;
use App\Support\Template;
use App\System\Auth;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\View\Interfaces\IBeLoggedMust;
use Symfony\Component\HttpFoundation\Request;

class BlockWallet extends Block implements IBeLoggedMust
{
    const BLOCK_ID = "wallet";

    /** @var Auth */
    private $auth;

    /** @var Template */
    private $template;

    /** @var UrlGenerator */
    private $url;

    /** @var UserServiceAccessService */
    private $userServiceAccessService;

    /** @var ServiceManager */
    private $serviceManager;

    /** @var Translator */
    private $lang;

    /** @var PriceTextService */
    private $priceTextService;

    public function __construct(
        Auth $auth,
        Template $template,
        UrlGenerator $url,
        UserServiceAccessService $userServiceAccessService,
        PriceTextService $priceTextService,
        ServiceManager $serviceManager,
        TranslationManager $translationManager
    ) {
        $this->auth = $auth;
        $this->template = $template;
        $this->url = $url;
        $this->userServiceAccessService = $userServiceAccessService;
        $this->serviceManager = $serviceManager;
        $this->priceTextService = $priceTextService;
        $this->lang = $translationManager->user();
    }

    public function getContentClass()
    {
        return "wallet-status";
    }

    public function getContentId()
    {
        return "wallet";
    }

    protected function content(Request $request, array $params)
    {
        $user = $this->auth->user();
        $balance = $this->priceTextService->getPlainPrice($user->getWallet());

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

        return $this->template->render("shop/layout/wallet", [
            "chargeWalletButton" => $chargeWalletButton,
            "balance" => $balance,
        ]);
    }
}
