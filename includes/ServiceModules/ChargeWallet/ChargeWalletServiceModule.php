<?php
namespace App\ServiceModules\ChargeWallet;

use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;
use App\Models\Purchase;
use App\Models\Service;
use App\Models\Transaction;
use App\Payment\General\BoughtServiceService;
use App\Payment\General\ChargeWalletFactory;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\ServiceModules\ServiceModule;
use App\Services\PriceTextService;
use App\System\Auth;
use App\System\Heart;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\Verification\Abstracts\SupportSms;
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

    /** @var Heart */
    private $heart;

    /** @var Translator */
    private $lang;

    /** @var Settings */
    private $settings;

    /** @var BoughtServiceService */
    private $boughtServiceService;

    /** @var PriceTextService */
    private $priceTextService;

    /** @var ChargeWalletFactory */
    private $chargeWalletFactory;

    public function __construct(Service $service = null)
    {
        parent::__construct($service);

        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $this->lang = $translationManager->user();
        $this->auth = $this->app->make(Auth::class);
        $this->heart = $this->app->make(Heart::class);
        $this->settings = $this->app->make(Settings::class);
        $this->boughtServiceService = $this->app->make(BoughtServiceService::class);
        $this->priceTextService = $this->app->make(PriceTextService::class);
        $this->chargeWalletFactory = $this->app->make(ChargeWalletFactory::class);
    }

    public function purchaseFormGet(array $query)
    {
        $optionSms = '';
        $optionTransfer = '';
        $smsBody = '';
        $transferBody = '';

        if ($this->settings->getSmsPlatformId()) {
            $paymentModule = $this->heart->getPaymentModuleByPlatformId(
                $this->settings->getSmsPlatformId()
            );

            if ($paymentModule instanceof SupportSms) {
                $optionSms = $this->template->render("services/charge_wallet/option_sms");

                $smsList = [];
                foreach ($paymentModule::getSmsNumbers() as $smsNumber) {
                    $provision = number_format($smsNumber->getProvision() / 100.0, 2);
                    $smsList[] = create_dom_element(
                        "option",
                        $this->lang->t(
                            'charge_sms_option',
                            $this->priceTextService->getPriceGrossText($smsNumber->getPrice()),
                            $this->settings->getCurrency(),
                            $provision,
                            $this->settings->getCurrency()
                        ),
                        [
                            'value' => $smsNumber->getPrice(),
                        ]
                    );
                }

                $smsBody = $this->template->render("services/charge_wallet/sms_body", [
                    'smsList' => implode("", $smsList),
                ]);
            }
        }

        if ($this->settings->getTransferPlatformId()) {
            $optionTransfer = $this->template->render("services/charge_wallet/option_transfer");
            $transferBody = $this->template->render("services/charge_wallet/transfer_body");
        }

        // TODO Allow displaying direct billing

        return $this->template->render(
            "services/charge_wallet/purchase_form",
            compact('optionSms', 'optionTransfer', 'smsBody', 'transferBody') + [
                'serviceId' => $this->service->getId(),
            ]
        );
    }

    public function purchaseFormValidate(Purchase $purchase, array $body)
    {
        $method = array_get($body, 'method');

        if (!$this->auth->check()) {
            throw new UnauthorizedException();
        }

        try {
            $paymentMethod = $this->chargeWalletFactory->create($method);
        } catch (InvalidArgumentException $e) {
            throw new ValidationException([
                "method" => "Invalid value",
            ]);
        }

        $purchase->setServiceId($this->service->getId());
        $purchase->setPayment([
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
        $quantity = number_format($purchase->getOrder(Purchase::ORDER_QUANTITY) / 100, 2);

        return $this->template->renderNoComments(
            "services/charge_wallet/order_details",
            compact('quantity')
        );
    }

    public function purchase(Purchase $purchase)
    {
        $this->chargeWallet(
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
            number_format($purchase->getOrder(Purchase::ORDER_QUANTITY) / 100, 2),
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

    /**
     * @param int $uid
     * @param int $quantity
     */
    private function chargeWallet($uid, $quantity)
    {
        $this->db
            ->statement("UPDATE `ss_users` SET `wallet` = `wallet` + ? WHERE `uid` = ?")
            ->execute([$quantity, $uid]);
    }
}
