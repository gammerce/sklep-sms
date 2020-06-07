<?php
namespace App\ServiceModules\ChargeWallet;

use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;
use App\Models\Purchase;
use App\Models\Service;
use App\Models\Transaction;
use App\Payment\General\BoughtServiceService;
use App\Payment\General\ChargeWalletFactory;
use App\Payment\General\PaymentOption;
use App\Payment\Wallet\WalletPaymentService;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\ServiceModules\ServiceModule;
use App\Services\PriceTextService;
use App\System\Auth;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\View\Interfaces\IBeLoggedMust;
use UnexpectedValueException;

// TODO Display more detailed information on payment box (sms, transfer, paypal etc.)
// TODO Add more options to charge wallet form (e.g. paypal)

class ChargeWalletServiceModule extends ServiceModule implements IServicePurchaseWeb, IBeLoggedMust
{
    const MODULE_ID = "charge_wallet";

    /** @var Auth */
    private $auth;

    /** @var Translator */
    private $lang;

    /** @var BoughtServiceService */
    private $boughtServiceService;

    /** @var PriceTextService */
    private $priceTextService;

    /** @var ChargeWalletFactory */
    private $chargeWalletFactory;

    /** @var WalletPaymentService */
    private $walletPaymentService;

    public function __construct(Service $service = null)
    {
        parent::__construct($service);

        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $this->lang = $translationManager->user();
        $this->auth = $this->app->make(Auth::class);
        $this->boughtServiceService = $this->app->make(BoughtServiceService::class);
        $this->priceTextService = $this->app->make(PriceTextService::class);
        $this->chargeWalletFactory = $this->app->make(ChargeWalletFactory::class);
        $this->walletPaymentService = $this->app->make(WalletPaymentService::class);
    }

    public function purchaseFormGet(array $query)
    {
        $paymentMethodOptions = [];
        $paymentMethodBodies = [];

        foreach ($this->chargeWalletFactory->createAll() as $paymentMethod) {
            $result = $paymentMethod->getOptionView();

            if ($result) {
                $paymentMethodOptions[] = $result[0];
                $paymentMethodBodies[] = $result[1];
            }
        }

        return $this->template->render("shop/services/charge_wallet/purchase_form", [
            "paymentMethodBodies" => implode("", $paymentMethodBodies),
            "paymentMethodOptions" => implode("<br />", $paymentMethodOptions),
            "serviceId" => $this->service->getId(),
        ]);
    }

    public function purchaseFormValidate(Purchase $purchase, array $body)
    {
        if (!$this->auth->check()) {
            throw new UnauthorizedException();
        }

        $paymentPlatformId = as_int(array_get($body, "payment_platform_id"));
        $method = as_payment_method(array_get($body, "method"));

        if (!$purchase->getPaymentSelect()->contains($method, $paymentPlatformId)) {
            throw new ValidationException([
                "method" => "Invalid payment method",
            ]);
        }

        $purchase
            ->setServiceId($this->service->getId())
            ->setPaymentOption(new PaymentOption($method, $paymentPlatformId));

        $this->chargeWalletFactory->create($method)->setup($purchase, $body);
    }

    public function orderDetails(Purchase $purchase)
    {
        $paymentMethod = $this->chargeWalletFactory->create(
            $purchase->getPaymentOption()->getPaymentMethod()
        );

        $price = $paymentMethod->getPrice($purchase);
        $quantity = $paymentMethod->getQuantity($purchase);

        return $this->template->renderNoComments(
            "shop/services/charge_wallet/order_details",
            compact("price", "quantity")
        );
    }

    public function purchase(Purchase $purchase)
    {
        $this->walletPaymentService->chargeWallet(
            $purchase->user->getId(),
            $purchase->getOrder(Purchase::ORDER_QUANTITY)
        );

        $promoCode = $purchase->getPromoCode();

        return $this->boughtServiceService->create(
            $purchase->user->getId(),
            $purchase->user->getUsername(),
            $purchase->user->getLastIp(),
            (string) $purchase->getPaymentOption()->getPaymentMethod(),
            $purchase->getPayment(Purchase::PAYMENT_PAYMENT_ID),
            $this->service->getId(),
            0,
            $purchase->getOrder(Purchase::ORDER_QUANTITY) / 100,
            $purchase->user->getUsername(),
            $purchase->getEmail(),
            $promoCode ? $promoCode->getCode() : null
        );
    }

    public function purchaseInfo($action, Transaction $transaction)
    {
        $quantity = $this->priceTextService->getPriceText(
            price_to_int($transaction->getQuantity())
        );

        if ($action === "web") {
            try {
                $paymentMethod = $this->chargeWalletFactory->create(
                    $transaction->getPaymentMethod()
                );
            } catch (UnexpectedValueException $e) {
                return "";
            }

            return $paymentMethod->getTransactionView($transaction);
        }

        if ($action === "payment_log") {
            return [
                "text" => $this->lang->t("wallet_was_charged", $quantity),
                "class" => "income",
            ];
        }

        return "";
    }
}
