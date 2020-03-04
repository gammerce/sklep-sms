<?php
namespace App\Payment\DirectBilling;

use App\Models\Purchase;
use App\Payment\Interfaces\IPaymentMethod;
use App\ServiceModules\ServiceModule;
use App\Services\PriceTextService;
use App\Support\Template;
use App\System\Heart;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\Verification\Abstracts\SupportDirectBilling;

class DirectBillingPaymentMethod implements IPaymentMethod
{
    /** @var Template */
    private $template;

    /** @var PriceTextService */
    private $priceTextService;

    /** @var Heart */
    private $heart;

    /** @var Translator */
    private $lang;

    public function __construct(
        Template $template,
        PriceTextService $priceTextService,
        Heart $heart,
        TranslationManager $translationManager
    ) {
        $this->template = $template;
        $this->priceTextService = $priceTextService;
        $this->heart = $heart;
        $this->lang = $translationManager->user();
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

    public function pay(Purchase $purchase, ServiceModule $serviceModule)
    {
        $paymentModule = $this->heart->getPaymentModuleByPlatformId(
            $purchase->getPayment(Purchase::PAYMENT_PLATFORM_DIRECT_BILLING)
        );

        if ($purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER) === null) {
            return [
                'status' => "no_transfer_price",
                'text' => $this->lang->t('payment_method_unavailable'),
                'positive' => false,
            ];
        }

        if (!($paymentModule instanceof SupportDirectBilling)) {
            return [
                'status' => "direct_billing_unavailable",
                'text' => $this->lang->t('direct_billing_unavailable'),
                'positive' => false,
            ];
        }

        // TODO Implement
        return [
            'status' => "not_implemented",
            'text' => "Not implemented",
            'positive' => false,
        ];
    }
}
