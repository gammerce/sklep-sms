<?php
namespace App\Payment\DirectBilling;

use App\Models\Purchase;
use App\Payment\Interfaces\IPurchaseRenderer;
use App\Services\PriceTextService;
use App\Support\Template;

class PurchaseRenderer implements IPurchaseRenderer
{
    /** @var Template */
    private $template;

    /** @var PriceTextService */
    private $priceTextService;

    public function __construct(Template $template, PriceTextService $priceTextService)
    {
        $this->template = $template;
        $this->priceTextService = $priceTextService;
    }

    public function render(Purchase $purchase)
    {
        $price = $this->priceTextService->getPriceText(
            $purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER)
        );
        return $this->template->render("payment_method_direct_billing", compact("price"));
    }

    public function isAvailable(Purchase $purchase)
    {
        return $purchase->getPayment(Purchase::PAYMENT_PLATFORM_DIRECT_BILLING) &&
            $purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER) !== null &&
            !$purchase->getPayment(Purchase::PAYMENT_DISABLED_DIRECT_BILLING);
    }
}
