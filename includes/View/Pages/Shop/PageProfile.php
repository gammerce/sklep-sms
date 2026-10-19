<?php
namespace App\View\Pages\Shop;

use App\Payment\Invoice\InvoiceService;
use App\Support\EmailChangeStatus;
use App\Theme\Template;
use App\System\Auth;
use App\Translation\TranslationManager;
use App\View\Interfaces\IBeLoggedMust;
use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

class PageProfile extends Page implements IBeLoggedMust
{
    const PAGE_ID = "profile";

    private Auth $auth;
    private InvoiceService $invoiceService;

    public function __construct(
        InvoiceService $invoiceService,
        Template $template,
        TranslationManager $translationManager,
        Auth $auth
    ) {
        parent::__construct($template, $translationManager);
        $this->auth = $auth;
        $this->invoiceService = $invoiceService;
    }

    public function getTitle(?Request $request = null): string
    {
        return $this->lang->t("profile");
    }

    public function getContent(Request $request)
    {
        $user = $this->auth->user();
        // getContactEmail() hides the internal Steam placeholder.
        $email = $user->getContactEmail();
        $needsEmail = !strlen($email);
        $username = $user->getUsername();
        $forename = $user->getForename();
        $surname = $user->getSurname();
        $steamId = $user->getSteamId();
        $steamVerified = $user->isSteamVerified();
        $hasPassword = $user->hasPassword();
        $canDisconnectSteam = $user->canUnlinkSteam();
        $needsPasswordToDisconnect = $steamVerified && !$canDisconnectSteam;
        $billingAddressName = $user->getBillingAddress()->getName();
        $billingAddressVatID = $user->getBillingAddress()->getVatID();
        $billingAddressStreet = $user->getBillingAddress()->getStreet();
        $billingAddressPostalCode = $user->getBillingAddress()->getPostalCode();
        $billingAddressCity = $user->getBillingAddress()->getCity();
        $billingAddressFormClass = $this->invoiceService->isConfigured() ? "" : "is-hidden";

        $steamError = $this->pullSessionMessage($request, "steam_error");
        $emailStatus = $this->queryMessage($request, "email_status", EmailChangeStatus::KEYS);

        return $this->template->render(
            "shop/pages/profile",
            compact(
                "email",
                "username",
                "forename",
                "surname",
                "steamId",
                "steamVerified",
                "hasPassword",
                "canDisconnectSteam",
                "needsPasswordToDisconnect",
                "needsEmail",
                "steamError",
                "emailStatus",
                "billingAddressName",
                "billingAddressVatID",
                "billingAddressStreet",
                "billingAddressPostalCode",
                "billingAddressCity",
                "billingAddressFormClass"
            )
        );
    }
}
