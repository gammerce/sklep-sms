<?php
namespace App\View\Blocks;

use App\Routing\UrlGenerator;
use App\Services\UserServiceAccessService;
use App\Support\Template;
use App\System\Auth;
use App\System\Heart;
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

    /** @var Heart */
    private $heart;

    /** @var Translator */
    private $lang;

    public function __construct(
        Auth $auth,
        Template $template,
        UrlGenerator $url,
        UserServiceAccessService $userServiceAccessService,
        Heart $heart,
        TranslationManager $translationManager
    ) {
        $this->auth = $auth;
        $this->template = $template;
        $this->url = $url;
        $this->userServiceAccessService = $userServiceAccessService;
        $this->heart = $heart;
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
        $balance = number_format($user->getWallet() / 100, 2);

        if (
            $this->userServiceAccessService->canUserUseService(
                $this->heart->getService("charge_wallet"),
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
