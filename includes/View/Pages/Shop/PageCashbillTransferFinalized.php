<?php
namespace App\View\Pages\Shop;

use App\Managers\PaymentModuleManager;
use App\Payment\General\PaymentMethod;
use App\Payment\General\PurchaseInformation;
use App\Support\Template;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

class PageCashbillTransferFinalized extends Page
{
    const PAGE_ID = "transfer_finalized";

    /** @var PurchaseInformation */
    private $purchaseInformation;

    /** @var Settings */
    private $settings;

    /** @var PaymentModuleManager */
    private $paymentModuleManager;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        PurchaseInformation $purchaseInformation,
        Settings $settings,
        PaymentModuleManager $paymentModuleManager
    ) {
        parent::__construct($template, $translationManager);

        $this->purchaseInformation = $purchaseInformation;
        $this->settings = $settings;
        $this->paymentModuleManager = $paymentModuleManager;
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("transfer_finalized");
    }

    public function getContent(Request $request)
    {
        $status = $request->query->get("status");
        $orderId = $request->query->get("orderid");

        if (strtoupper($status) != "OK") {
            return $this->template->render("shop/components/general/header", [
                "title" => $this->getTitle($request),
                "subtitle" => $this->lang->t("transfer_error"),
            ]);
        }

        $content = $this->purchaseInformation->get([
            "payment" => PaymentMethod::TRANSFER(),
            "payment_id" => $orderId,
            "action" => "web",
        ]);

        return $this->template->render("shop/pages/transfer_finalized", [
            "title" => $this->getTitle($request),
            "subtitle" => $this->lang->t("transfer_error"),
            "content" => $content,
        ]);
    }
}
