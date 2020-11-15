<?php
namespace App\ServiceModules\ChargeWallet;

use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;
use App\Models\Purchase;
use App\Models\Service;
use App\Models\Transaction;
use App\Payment\General\BoughtServiceService;
use App\Payment\General\ChargeWalletFactory;
use App\Payment\General\PaymentMethod;
use App\Payment\General\PaymentOption;
use App\Payment\Wallet\WalletPaymentService;
use App\Repositories\PaymentPlatformRepository;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\ServiceModules\ServiceModule;
use App\Services\PriceTextService;
use App\Services\ServiceDescriptionService;
use App\Support\Money;
use App\Support\Template;
use App\System\Auth;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\View\Interfaces\IBeLoggedMust;
use UnexpectedValueException;

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

    /** @var PaymentPlatformRepository */
    private $paymentPlatformRepository;

    /** @var Settings */
    private $settings;

    public function __construct(
        Auth $auth,
        BoughtServiceService $boughtServiceService,
        ChargeWalletFactory $chargeWalletFactory,
        PaymentPlatformRepository $paymentPlatformRepository,
        PriceTextService $priceTextService,
        ServiceDescriptionService $serviceDescriptionService,
        Settings $settings,
        Template $template,
        TranslationManager $translationManager,
        WalletPaymentService $walletPaymentService,
        Service $service = null
    ) {
        parent::__construct($template, $serviceDescriptionService, $service);
        $this->lang = $translationManager->user();
        $this->auth = $auth;
        $this->boughtServiceService = $boughtServiceService;
        $this->chargeWalletFactory = $chargeWalletFactory;
        $this->paymentPlatformRepository = $paymentPlatformRepository;
        $this->priceTextService = $priceTextService;
        $this->settings = $settings;
        $this->walletPaymentService = $walletPaymentService;
    }

    public function purchaseFormGet(array $query)
    {
        $paymentMethodOptions = [];
        $paymentMethodBodies = [];

        foreach ($this->getPaymentOptions() as $paymentOption) {
            $paymentMethod = $this->chargeWalletFactory->create($paymentOption->getPaymentMethod());
            $paymentPlatform = $this->paymentPlatformRepository->get(
                $paymentOption->getPaymentPlatformId()
            );
            $result = $paymentMethod->getOptionView($paymentPlatform);

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

    /**
     * @return PaymentOption[]
     */
    private function getPaymentOptions()
    {
        $output = [];

        if ($this->settings->getSmsPlatformId()) {
            $output[] = new PaymentOption(
                PaymentMethod::SMS(),
                $this->settings->getSmsPlatformId()
            );
        }

        if ($this->settings->getDirectBillingPlatformId()) {
            $output[] = new PaymentOption(
                PaymentMethod::DIRECT_BILLING(),
                $this->settings->getDirectBillingPlatformId()
            );
        }

        foreach ($this->settings->getTransferPlatformIds() as $paymentPlatformId) {
            $output[] = new PaymentOption(PaymentMethod::TRANSFER(), $paymentPlatformId);
        }

        return $output;
    }

    public function purchaseFormValidate(Purchase $purchase, array $body)
    {
        if (!$this->auth->check()) {
            throw new UnauthorizedException();
        }

        $paymentOption = as_string(array_get($body, "payment_option"));
        $exploded = explode(",", $paymentOption);
        $paymentMethod = as_payment_method(array_get($exploded, 0));
        $paymentPlatformId = as_int(array_get($exploded, 1));

        $paymentOption = new PaymentOption($paymentMethod, $paymentPlatformId);

        if (!$purchase->getPaymentSelect()->contains($paymentOption)) {
            throw new ValidationException([
                "payment_option" => "Invalid payment option",
            ]);
        }

        $purchase->getPaymentSelect()->allowPaymentOption($paymentOption);

        $purchase->setServiceId($this->service->getId())->setPaymentOption($paymentOption);

        $this->chargeWalletFactory->create($paymentMethod)->setup($purchase, $body);
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
            $purchase->user,
            $purchase->getOrder(Purchase::ORDER_QUANTITY)
        );

        $promoCode = $purchase->getPromoCode();

        return $this->boughtServiceService->create(
            $purchase->user->getId(),
            $purchase->user->getUsername(),
            $purchase->getAddressIp(),
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
            Money::fromPrice($transaction->getQuantity())
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
