<?php
namespace App\View\Pages\Shop;

use App\Payment\General\PaymentMethod;
use App\Payment\General\PurchaseInformation;
use App\Theme\Template;
use App\Translation\TranslationManager;
use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

class PageCashBillTransferFinalized extends Page
{
    const PAGE_ID = "transfer_finalized";

    private PurchaseInformation $purchaseInformation;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        PurchaseInformation $purchaseInformation
    ) {
        parent::__construct($template, $translationManager);
        $this->purchaseInformation = $purchaseInformation;
    }

    public function getTitle(Request $request): string
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

        return $this->template->render("shop/pages/payment_success", [
            "title" => $this->getTitle($request),
            "content" => $content,
        ]);
    }
}
