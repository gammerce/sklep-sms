<?php
namespace App\Payment\Transfer;

use App\Models\Purchase;
use App\Payment\Interfaces\IPaymentMethod;
use App\ServiceModules\ServiceModule;
use App\Services\PriceTextService;
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

    public function pay(Purchase $purchase, ServiceModule $serviceModule)
    {
        $paymentModule = $this->heart->getPaymentModuleByPlatformId(
            $purchase->getPayment(Purchase::PAYMENT_PLATFORM_TRANSFER)
        );

        // TODO Do not return arrays

        if ($purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER) === null) {
            return [
                'status' => "no_transfer_price",
                'text' => $this->lang->t('payment_method_unavailable'),
                'positive' => false,
            ];
        }

        if ($purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER) <= 100) {
            return [
                'status' => "too_little_for_transfer",
                'text' => $this->lang->t('transfer_above_amount', $this->settings->getCurrency()),
                'positive' => false,
            ];
        }

        if (!($paymentModule instanceof SupportTransfer)) {
            return [
                'status' => "transfer_unavailable",
                'text' => $this->lang->t('transfer_unavailable'),
                'positive' => false,
            ];
        }

        $purchase->setDesc(
            $this->lang->t('payment_for_service', $serviceModule->service->getName())
        );

        return $this->transferPaymentService->payWithTransfer($paymentModule, $purchase);
    }
}
