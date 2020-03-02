<?php
namespace App\Payment\Wallet;

use App\Models\Purchase;
use App\Payment\Interfaces\IPaymentMethod;
use App\ServiceModules\ServiceModule;
use App\Services\PriceTextService;
use App\Support\Template;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class WalletPaymentMethod implements IPaymentMethod
{
    /** @var Template */
    private $template;

    /** @var PriceTextService */
    private $priceTextService;

    /** @var Translator */
    private $lang;

    /** @var WalletPaymentService */
    private $walletPaymentService;

    public function __construct(
        Template $template,
        PriceTextService $priceTextService,
        TranslationManager $translationManager,
        WalletPaymentService $walletPaymentService
    ) {
        $this->template = $template;
        $this->priceTextService = $priceTextService;
        $this->lang = $translationManager->user();
        $this->walletPaymentService = $walletPaymentService;
    }

    public function render(Purchase $purchase)
    {
        $price = $this->priceTextService->getPriceText(
            $purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER)
        );

        return $this->template->render("payment_method_wallet", compact('price'));
    }

    public function isAvailable(Purchase $purchase)
    {
        return is_logged() &&
            $purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER) !== null &&
            !$purchase->getPayment(Purchase::PAYMENT_DISABLED_WALLET);
    }

    public function pay(Purchase $purchase, ServiceModule $serviceModule)
    {
        if (!$purchase->user->exists()) {
            return [
                'status' => "wallet_not_logged",
                'text' => $this->lang->t('no_login_no_wallet'),
                'positive' => false,
            ];
        }

        if ($purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER) === null) {
            return [
                'status' => "no_transfer_price",
                'text' => $this->lang->t('payment_method_unavailable'),
                'positive' => false,
            ];
        }

        $paymentId = $this->walletPaymentService->payWithWallet(
            $purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER),
            $purchase->user
        );

        if (is_array($paymentId)) {
            return $paymentId;
        }

        $purchase->setPayment([
            Purchase::PAYMENT_PAYMENT_ID => $paymentId,
        ]);
        $boughtServiceId = $serviceModule->purchase($purchase);

        return [
            'status' => "purchased",
            'text' => $this->lang->t('purchase_success'),
            'positive' => true,
            'data' => ['bsid' => $boughtServiceId],
        ];
    }
}
