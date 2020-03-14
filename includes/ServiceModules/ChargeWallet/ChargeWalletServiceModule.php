<?php
namespace App\ServiceModules\ChargeWallet;

use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;
use App\Models\Purchase;
use App\Models\Service;
use App\Models\Transaction;
use App\Payment\General\BoughtServiceService;
use App\Payment\General\ChargeWalletFactory;
use App\Payment\Wallet\WalletPaymentService;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\ServiceModules\ServiceModule;
use App\Services\PriceTextService;
use App\System\Auth;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\View\Interfaces\IBeLoggedMust;
use InvalidArgumentException;

class ChargeWalletServiceModule extends ServiceModule implements
    IServicePurchase,
    IServicePurchaseWeb,
    IBeLoggedMust
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

        return $this->template->render("services/charge_wallet/purchase_form", [
            'paymentMethodBodies' => implode("", $paymentMethodBodies),
            'paymentMethodOptions' => implode("<br />", $paymentMethodOptions),
            'serviceId' => $this->service->getId(),
        ]);
    }

    public function purchaseFormValidate(Purchase $purchase, array $body)
    {
        if (!$this->auth->check()) {
            throw new UnauthorizedException();
        }

        $method = array_get($body, 'method');

        try {
            $paymentMethod = $this->chargeWalletFactory->create($method);
        } catch (InvalidArgumentException $e) {
            throw new ValidationException([
                "method" => "Invalid value",
            ]);
        }

        $purchase->setServiceId($this->service->getId());
        $purchase->setPayment([
            Purchase::PAYMENT_METHOD => $method,
            Purchase::PAYMENT_DISABLED_DIRECT_BILLING => true,
            Purchase::PAYMENT_DISABLED_SERVICE_CODE => true,
            Purchase::PAYMENT_DISABLED_SMS => true,
            Purchase::PAYMENT_DISABLED_TRANSFER => true,
            Purchase::PAYMENT_DISABLED_WALLET => true,
        ]);

        $paymentMethod->setup($purchase, $body);
    }

    public function orderDetails(Purchase $purchase)
    {
        $paymentMethod = $this->chargeWalletFactory->create(
            $purchase->getPayment(Purchase::PAYMENT_METHOD)
        );

        $price = $paymentMethod->getPrice($purchase);
        $quantity = $paymentMethod->getQuantity($purchase);

        return $this->template->renderNoComments("services/charge_wallet/order_details", [
            'price' => $this->priceTextService->getPriceText($price),
            'quantity' => $this->priceTextService->getPriceText($quantity),
        ]);
    }

    public function purchase(Purchase $purchase)
    {
        $this->walletPaymentService->chargeWallet(
            $purchase->user->getUid(),
            $purchase->getOrder(Purchase::ORDER_QUANTITY)
        );

        return $this->boughtServiceService->create(
            $purchase->user->getUid(),
            $purchase->user->getUsername(),
            $purchase->user->getLastIp(),
            $purchase->getPayment(Purchase::PAYMENT_METHOD),
            $purchase->getPayment(Purchase::PAYMENT_PAYMENT_ID),
            $this->service->getId(),
            0,
            $purchase->getOrder(Purchase::ORDER_QUANTITY) / 100,
            $purchase->user->getUsername(),
            $purchase->getEmail()
        );
    }

    public function purchaseInfo($action, Transaction $transaction)
    {
        $quantity = $this->priceTextService->getPriceText($transaction->getQuantity() * 100);

        if ($action === "web") {
            try {
                $paymentMethod = $this->chargeWalletFactory->create(
                    $transaction->getPaymentMethod()
                );
            } catch (InvalidArgumentException $e) {
                return '';
            }

            return $paymentMethod->getTransactionView($transaction);
        }

        if ($action === "payment_log") {
            return [
                'text' => $this->lang->t('wallet_was_charged', $quantity),
                'class' => "income",
            ];
        }

        return '';
    }
}
