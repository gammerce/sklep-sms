<?php
namespace App\Payment\Wallet;

use App\Models\Purchase;
use App\Payment\Interfaces\IPaymentMethod;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\Services\PriceTextService;
use App\Support\Result;
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

        return $this->template->render("shop/payment/payment_method_wallet", compact('price'));
    }

    public function isAvailable(Purchase $purchase)
    {
        return is_logged() &&
            $purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER) !== null &&
            !$purchase->getPayment(Purchase::PAYMENT_DISABLED_WALLET);
    }

    public function pay(Purchase $purchase, IServicePurchase $serviceModule)
    {
        if (!$purchase->user->exists()) {
            return new Result("wallet_not_logged", $this->lang->t('no_login_no_wallet'), false);
        }

        if ($purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER) === null) {
            return new Result(
                "no_transfer_price",
                $this->lang->t('payment_method_unavailable'),
                false
            );
        }

        try {
            $paymentId = $this->walletPaymentService->payWithWallet(
                $purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER),
                $purchase->user
            );
        } catch (NotEnoughFundsException $e) {
            return new Result("no_money", $this->lang->t('not_enough_money'), false);
        }

        $purchase->setPayment([
            Purchase::PAYMENT_PAYMENT_ID => $paymentId,
        ]);
        $boughtServiceId = $serviceModule->purchase($purchase);

        return new Result("purchased", $this->lang->t('purchase_success'), true, [
            'bsid' => $boughtServiceId,
        ]);
    }
}
