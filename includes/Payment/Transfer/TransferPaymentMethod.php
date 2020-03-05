<?php
namespace App\Payment\Transfer;

use App\Models\Purchase;
use App\Payment\Interfaces\IPaymentMethod;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\Services\PriceTextService;
use App\Support\Result;
use App\Support\Template;
use App\System\Heart;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\Verification\Abstracts\SupportTransfer;

class TransferPaymentMethod implements IPaymentMethod
{
    /** @var Template */
    private $template;

    /** @var PriceTextService */
    private $priceTextService;

    /** @var Heart */
    private $heart;

    /** @var TransferPaymentService */
    private $transferPaymentService;

    /** @var Translator */
    private $lang;

    /** @var Settings */
    private $settings;

    public function __construct(
        Heart $heart,
        Template $template,
        PriceTextService $priceTextService,
        TransferPaymentService $transferPaymentService,
        TranslationManager $translationManager,
        Settings $settings
    ) {
        $this->template = $template;
        $this->priceTextService = $priceTextService;
        $this->heart = $heart;
        $this->transferPaymentService = $transferPaymentService;
        $this->lang = $translationManager->user();
        $this->settings = $settings;
    }

    public function render(Purchase $purchase)
    {
        $price = $this->priceTextService->getPriceText(
            $purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER)
        );

        return $this->template->render("payment_method_transfer", compact('price'));
    }

    public function isAvailable(Purchase $purchase)
    {
        return $purchase->getPayment(Purchase::PAYMENT_PLATFORM_TRANSFER) &&
            $purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER) !== null &&
            $purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER) > 1 &&
            !$purchase->getPayment(Purchase::PAYMENT_DISABLED_TRANSFER);
    }

    public function pay(Purchase $purchase, IServicePurchase $serviceModule)
    {
        $paymentModule = $this->heart->getPaymentModuleByPlatformId(
            $purchase->getPayment(Purchase::PAYMENT_PLATFORM_TRANSFER)
        );

        if ($purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER) === null) {
            return new Result(
                "no_transfer_price",
                $this->lang->t('payment_method_unavailable'),
                false
            );
        }

        if ($purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER) <= 100) {
            return new Result(
                "too_little_for_transfer",
                $this->lang->t('transfer_above_amount', $this->settings->getCurrency()),
                false
            );
        }

        if (!($paymentModule instanceof SupportTransfer)) {
            return new Result(
                "transfer_unavailable",
                $this->lang->t('transfer_unavailable'),
                false
            );
        }

        // TODO Handle it
        $purchase->setDesc(
            $this->lang->t('payment_for_service', $serviceModule->service->getName())
        );

        return $this->transferPaymentService->payWithTransfer($paymentModule, $purchase);
    }
}
