<?php
namespace App\Payment\Wallet;

use App\Models\Purchase;
use App\Payment\Exceptions\PaymentProcessingException;
use App\Payment\General\PaymentResult;
use App\Payment\General\PaymentResultType;
use App\Payment\Interfaces\IPaymentMethod;
use App\Payment\Transfer\TransferPriceService;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\System\Auth;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class WalletPaymentMethod implements IPaymentMethod
{
    /** @var Translator */
    private $lang;

    /** @var WalletPaymentService */
    private $walletPaymentService;

    /** @var TransferPriceService */
    private $transferPriceService;

    /** @var Auth */
    private $auth;

    public function __construct(
        TranslationManager $translationManager,
        TransferPriceService $transferPriceService,
        WalletPaymentService $walletPaymentService,
        Auth $auth
    ) {
        $this->lang = $translationManager->user();
        $this->walletPaymentService = $walletPaymentService;
        $this->transferPriceService = $transferPriceService;
        $this->auth = $auth;
    }

    public function getPaymentDetails(Purchase $purchase)
    {
        return $this->transferPriceService->getOldAndNewPrice($purchase);
    }

    public function isAvailable(Purchase $purchase)
    {
        return $this->auth->check() &&
            $this->transferPriceService->getPrice($purchase) !== null &&
            !$purchase->getPayment(Purchase::PAYMENT_DISABLED_WALLET);
    }

    /**
     * @param Purchase $purchase
     * @param IServicePurchase $serviceModule
     * @return PaymentResult
     * @throws PaymentProcessingException
     */
    public function pay(Purchase $purchase, IServicePurchase $serviceModule)
    {
        if (!$purchase->user->exists()) {
            throw new PaymentProcessingException(
                "wallet_not_logged",
                $this->lang->t("no_login_no_wallet")
            );
        }

        $price = $this->transferPriceService->getPrice($purchase);

        if ($price === null) {
            throw new PaymentProcessingException(
                "no_transfer_price",
                $this->lang->t("payment_method_unavailable")
            );
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

        return new PaymentResult(PaymentResultType::PURCHASED(), $boughtServiceId);
    }
}
