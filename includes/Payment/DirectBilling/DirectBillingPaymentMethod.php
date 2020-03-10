<?php
namespace App\Payment\DirectBilling;

use App\Models\Purchase;
use App\Payment\General\ExternalPaymentService;
use App\Payment\Interfaces\IPaymentMethod;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\Services\PriceTextService;
use App\Support\Result;
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

    /** @var ExternalPaymentService */
    private $externalPaymentService;

    public function __construct(
        Template $template,
        PriceTextService $priceTextService,
        Heart $heart,
        ExternalPaymentService $externalPaymentService,
        TranslationManager $translationManager
    ) {
        $this->template = $template;
        $this->priceTextService = $priceTextService;
        $this->heart = $heart;
        $this->lang = $translationManager->user();
        $this->externalPaymentService = $externalPaymentService;
    }

    public function render(Purchase $purchase)
    {
        $price = $this->priceTextService->getPriceText(
            $purchase->getPayment(Purchase::PAYMENT_PRICE_DIRECT_BILLING)
        );
        return $this->template->render("payment/payment_method_direct_billing", compact("price"));
    }

    public function isAvailable(Purchase $purchase)
    {
        return $purchase->getPayment(Purchase::PAYMENT_PLATFORM_DIRECT_BILLING) &&
            $purchase->getPayment(Purchase::PAYMENT_PRICE_DIRECT_BILLING) !== null &&
            !$purchase->getPayment(Purchase::PAYMENT_DISABLED_DIRECT_BILLING);
    }

    public function pay(Purchase $purchase, IServicePurchase $serviceModule)
    {
        $paymentModule = $this->heart->getPaymentModuleByPlatformId(
            $purchase->getPayment(Purchase::PAYMENT_PLATFORM_DIRECT_BILLING)
        );

        if ($purchase->getPayment(Purchase::PAYMENT_PRICE_DIRECT_BILLING) === null) {
            return new Result(
                "no_transfer_price",
                $this->lang->t('payment_method_unavailable'),
                false
            );
        }

        if (!($paymentModule instanceof SupportDirectBilling)) {
            return new Result(
                "direct_billing_unavailable",
                $this->lang->t('direct_billing_unavailable'),
                false
            );
        }

        $fileName = $this->externalPaymentService->storePurchase($purchase);

        return $paymentModule->prepareDirectBilling($purchase, $fileName);
    }
}
