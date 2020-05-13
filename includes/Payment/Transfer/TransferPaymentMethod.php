<?php
namespace App\Payment\Transfer;

use App\Managers\PaymentModuleManager;
use App\Managers\ServiceManager;
use App\Models\Purchase;
use App\Payment\General\PurchaseDataService;
use App\Payment\Interfaces\IPaymentMethod;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\Services\PriceTextService;
use App\Support\Result;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\Verification\Abstracts\SupportTransfer;

class TransferPaymentMethod implements IPaymentMethod
{
    /** @var PriceTextService */
    private $priceTextService;

    /** @var ServiceManager */
    private $serviceManager;

    /** @var Translator */
    private $lang;

    /** @var Settings */
    private $settings;

    /** @var PurchaseDataService */
    private $purchaseDataService;

    /** @var PaymentModuleManager */
    private $paymentModuleManager;

    public function __construct(
        ServiceManager $serviceManager,
        PriceTextService $priceTextService,
        PurchaseDataService $purchaseDataService,
        TranslationManager $translationManager,
        PaymentModuleManager $paymentModuleManager,
        Settings $settings
    ) {
        $this->priceTextService = $priceTextService;
        $this->serviceManager = $serviceManager;
        $this->lang = $translationManager->user();
        $this->settings = $settings;
        $this->purchaseDataService = $purchaseDataService;
        $this->paymentModuleManager = $paymentModuleManager;
    }

    public function getPaymentDetails(Purchase $purchase)
    {
        $price = $this->priceTextService->getPriceText(
            $purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER)
        );
        return compact("price");
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
        $paymentModule = $this->paymentModuleManager->getByPlatformId(
            $purchase->getPayment(Purchase::PAYMENT_PLATFORM_TRANSFER)
        );

        if ($purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER) === null) {
            return new Result(
                "no_transfer_price",
                $this->lang->t("payment_method_unavailable"),
                false
            );
        }

        if ($purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER) <= 100) {
            return new Result(
                "too_little_for_transfer",
                $this->lang->t("transfer_above_amount", $this->settings->getCurrency()),
                false
            );
        }

        if (!($paymentModule instanceof SupportTransfer)) {
            return new Result(
                "transfer_unavailable",
                $this->lang->t("transfer_unavailable"),
                false
            );
        }

        $service = $this->serviceManager->getService($purchase->getServiceId());
        $purchase->setDesc($this->lang->t("payment_for_service", $service->getNameI18n()));

        $fileName = $this->purchaseDataService->storePurchase($purchase);

        return new Result("external", $this->lang->t("external_payment_prepared"), true, [
            "data" => $paymentModule->prepareTransfer($purchase, $fileName),
        ]);
    }
}
