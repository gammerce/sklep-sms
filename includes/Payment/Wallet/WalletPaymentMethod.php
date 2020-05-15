<?php
namespace App\Payment\Wallet;

use App\Models\Purchase;
use App\Payment\Exceptions\PaymentProcessingException;
use App\Payment\Interfaces\IPaymentMethod;
use App\PromoCode\PromoCodeService;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\Services\PriceTextService;
use App\Support\Result;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class WalletPaymentMethod implements IPaymentMethod
{
    /** @var PriceTextService */
    private $priceTextService;

    /** @var Translator */
    private $lang;

    /** @var WalletPaymentService */
    private $walletPaymentService;

    /** @var PromoCodeService */
    private $promoCodeService;

    public function __construct(
        PriceTextService $priceTextService,
        PromoCodeService $promoCodeService,
        TranslationManager $translationManager,
        WalletPaymentService $walletPaymentService
    ) {
        $this->priceTextService = $priceTextService;
        $this->lang = $translationManager->user();
        $this->walletPaymentService = $walletPaymentService;
        $this->promoCodeService = $promoCodeService;
    }

    public function getPaymentDetails(Purchase $purchase)
    {
        $price = $purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER);
        $promoCode = $purchase->getPromoCode();

        if ($promoCode) {
            $discountedPrice = $this->promoCodeService->applyDiscount($promoCode, $price);

            return [
                "price" => $this->priceTextService->getPriceText($discountedPrice),
                "old_price" => $this->priceTextService->getPlainPrice($price),
            ];
        }

        return [
            "price" => $this->priceTextService->getPriceText($price),
        ];
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
            throw new PaymentProcessingException(
                "wallet_not_logged",
                $this->lang->t("no_login_no_wallet")
            );
        }

        $price = $purchase->getPayment(Purchase::PAYMENT_PRICE_TRANSFER);
        $promoCode = $purchase->getPromoCode();

        if ($price === null) {
            throw new PaymentProcessingException(
                "no_transfer_price",
                $this->lang->t("payment_method_unavailable")
            );
        }

        if ($promoCode) {
            $price = $this->promoCodeService->applyDiscount($promoCode, $price);
        }

        try {
            $paymentId = $this->walletPaymentService->payWithWallet($price, $purchase->user);
        } catch (NotEnoughFundsException $e) {
            throw new PaymentProcessingException("no_money", $this->lang->t("not_enough_money"));
        }

        $purchase->setPayment([
            Purchase::PAYMENT_PAYMENT_ID => $paymentId,
        ]);
        $boughtServiceId = $serviceModule->purchase($purchase);

        return new Result("purchased", $this->lang->t("purchase_success"), true, [
            "bsid" => $boughtServiceId,
        ]);
    }
}
