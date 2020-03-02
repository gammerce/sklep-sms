<?php
namespace App\Payment\Transfer;

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
        $transferPrice = $this->priceTextService->getPriceText(
            $purchase->getPayment(Purchase::PAYMENT_TRANSFER_PRICE)
        );

        return $this->template->render("payment_method_transfer", compact('transferPrice'));
    }

    public function isAvailable(Purchase $purchase)
    {
        return $purchase->getPayment(Purchase::PAYMENT_TRANSFER_PLATFORM) &&
            $purchase->getPayment(Purchase::PAYMENT_TRANSFER_PRICE) !== null &&
            $purchase->getPayment(Purchase::PAYMENT_TRANSFER_PRICE) > 1 &&
            !$purchase->getPayment(Purchase::PAYMENT_TRANSFER_DISABLED);
    }
}
