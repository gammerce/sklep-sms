<?php
namespace App\View\Renders;

use App\Models\QuantityPrice;
use App\Models\Service;
use App\Support\PriceTextService;
use App\Theme\Template;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class PurchasePriceRenderer
{
    private Settings $settings;
    private Template $template;
    private Translator $lang;
    private PriceTextService $priceTextService;

    public function __construct(
        Settings $settings,
        TranslationManager $translationManager,
        PriceTextService $priceTextService,
        Template $template
    ) {
        $this->settings = $settings;
        $this->template = $template;
        $this->lang = $translationManager->user();
        $this->priceTextService = $priceTextService;
    }

    public function render(QuantityPrice $price, Service $service): string
    {
        return $this->template->renderNoComments("shop/components/purchase/purchase_price", [
            "directBillingDiscount" => $price->directBillingDiscount,
            "directBillingPrice" => $this->priceTextService->getPriceText(
                $price->directBillingPrice
            ),
            "quantity" => $this->priceTextService->getQuantityText($price->getQuantity(), $service),
            "smsDiscount" => $price->smsDiscount,
            "smsPrice" => $this->priceTextService->getPriceGrossText($price->smsPrice),
            "transferDiscount" => $price->transferDiscount,
            "transferPrice" => $this->priceTextService->getPriceText($price->transferPrice),
            "value" => $price->getQuantity(),
        ]);
    }
}
