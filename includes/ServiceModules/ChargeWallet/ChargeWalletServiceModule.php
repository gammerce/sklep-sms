<?php
namespace App\ServiceModules\ChargeWallet;

use App\Models\Purchase;
use App\Models\Service;
use App\Payment\BoughtServiceService;
use App\Repositories\SmsPriceRepository;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\ServiceModules\ServiceModule;
use App\System\Auth;
use App\System\Heart;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\View\Interfaces\IBeLoggedMust;

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

    /** @var SmsPriceRepository */
    private $smsPriceRepository;

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
        $this->smsPriceRepository = $this->app->make(SmsPriceRepository::class);
    }

    public function purchaseFormGet(array $query)
    {
        $optionSms = '';
        $optionTransfer = '';
        $smsBody = '';
        $transferBody = '';

        if ($this->settings->getSmsPlatformId()) {
            $paymentModule = $this->heart->getPaymentModuleByPlatformIdOrFail(
                $this->settings->getSmsPlatformId()
            );

            $optionSms = $this->template->render("services/charge_wallet/option_sms");

            $smsList = [];
            foreach ($paymentModule->getTariffs() as $tariff) {
                $provision = number_format($tariff->getProvision() / 100.0, 2);
                $smsList[] = create_dom_element(
                    "option",
                    $this->lang->t(
                        'charge_sms_option',
                        $tariff->getSmsCostGross(),
                        $this->settings->getCurrency(),
                        $provision,
                        $this->settings->getCurrency()
                    ),
                    [
                        'value' => $tariff->getId(),
                    ]
                );
            }

            $smsBody = $this->template->render("services/charge_wallet/sms_body", [
                'smsList' => implode("", $smsList),
            ]);
        }

        if ($this->settings->getTransferPlatformId()) {
            $optionTransfer = $this->template->render("services/charge_wallet/option_transfer");
            $transferBody = $this->template->render("services/charge_wallet/transfer_body");
        }

        return $this->template->render(
            "services/charge_wallet/purchase_form",
            compact('optionSms', 'optionTransfer', 'smsBody', 'transferBody') + [
                'serviceId' => $this->service->getId(),
            ]
        );
    }

    public function purchaseFormValidate(array $body)
    {
        if (!$this->auth->check()) {
            return [
                'status' => "no_access",
                'text' => $this->lang->t('not_logged_or_no_perm'),
                'positive' => false,
            ];
        }

        // There are only two allowed ways to charge wallet
        if (!in_array($body['method'], [Purchase::METHOD_SMS, Purchase::METHOD_TRANSFER])) {
            return [
                'status' => "wrong_method",
                'text' => $this->lang->t('wrong_charge_method'),
                'positive' => false,
            ];
        }

        $smsPrice = array_get($body, 'sms_price');
        $transferPrice = array_get($body, 'transfer_amount');

        $warnings = [];

        if ($body['method'] == Purchase::METHOD_SMS) {
            if (!$smsPrice || !$this->smsPriceRepository->exists($smsPrice)) {
                $warnings['price_id'][] = $this->lang->t('charge_amount_not_chosen');
            }
        } elseif ($body['method'] == Purchase::METHOD_TRANSFER) {
            if ($warning = check_for_warnings("number", $transferPrice)) {
                $warnings['transfer_amount'] = array_merge(
                    (array) $warnings['transfer_amount'],
                    $warning
                );
            }

            if ($transferPrice <= 1) {
                $warnings['transfer_amount'][] = $this->lang->t(
                    'charge_amount_too_low',
                    "1.00 " . $this->settings->getCurrency()
                );
            }
        }

        if ($warnings) {
            return [
                'status' => "warnings",
                'text' => $this->lang->t('form_wrong_filled'),
                'positive' => false,
                'data' => ['warnings' => $warnings],
            ];
        }

        $purchase = new Purchase($this->auth->user());
        $purchase->setService($this->service->getId());
        // TODO Replace all setTariff
        // TODO Check charging wallet cause no price is set here
        $purchase->setPayment([
            'no_wallet' => true,
        ]);

        if ($body['method'] == Purchase::METHOD_SMS) {
            $purchase->setPayment([
                Purchase::PAYMENT_TRANSFER_DISABLED => true,
            ]);
            $purchase->setOrder([
                Purchase::ORDER_QUANTITY => get_sms_provision($smsPrice),
            ]);
        } elseif ($body['method'] == Purchase::METHOD_TRANSFER) {
            $purchase->setPayment([
                Purchase::PAYMENT_TRANSFER_PRICE => $transferPrice * 100,
                Purchase::PAYMENT_SMS_DISABLED => true,
            ]);
            $purchase->setOrder([
                Purchase::ORDER_QUANTITY => $transferPrice * 100,
            ]);
        }

        return [
            'status' => "ok",
            'text' => $this->lang->t('purchase_form_validated'),
            'positive' => true,
            'purchase_data' => $purchase,
        ];
    }

    public function orderDetails(Purchase $purchase)
    {
        $amount = number_format($purchase->getOrder(Purchase::ORDER_QUANTITY) / 100, 2);

        return $this->template->render(
            "services/charge_wallet/order_details",
            compact('amount'),
            true,
            false
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
            $purchase->getPayment('method'),
            $purchase->getPayment('payment_id'),
            $this->service->getId(),
            0,
            number_format($purchase->getOrder(Purchase::ORDER_QUANTITY) / 100, 2),
            $purchase->user->getUsername(),
            $purchase->getEmail()
        );
    }

    public function purchaseInfo($action, array $data)
    {
        $cost = $data['cost'] ?: 0;
        $data['amount'] .= ' ' . $this->settings->getCurrency();
        $data['cost'] = number_format($cost / 100, 2) . ' ' . $this->settings->getCurrency();

        if ($action == "web") {
            if ($data['payment'] == Purchase::METHOD_SMS) {
                $desc = $this->lang->t('wallet_was_charged', $data['amount']);
                return $this->template->render(
                    "services/charge_wallet/web_purchase_info_sms",
                    compact('desc', 'data'),
                    true,
                    false
                );
            }

            if ($data['payment'] == Purchase::METHOD_TRANSFER) {
                return $this->template->render(
                    "services/charge_wallet/web_purchase_info_transfer",
                    compact('data'),
                    true,
                    false
                );
            }

            return '';
        }

        if ($action == "payment_log") {
            return [
                'text' => $this->lang->t('wallet_was_charged', $data['amount']),
                'class' => "income",
            ];
        }

        return '';
    }

    public function descriptionShortGet()
    {
        return $this->service->getDescription();
    }

    /**
     * @param int $uid
     * @param int $amount
     */
    private function chargeWallet($uid, $amount)
    {
        $this->db->query(
            $this->db->prepare(
                "UPDATE `" .
                    TABLE_PREFIX .
                    "users` " .
                    "SET `wallet` = `wallet` + '%d' " .
                    "WHERE `uid` = '%d'",
                [$amount, $uid]
            )
        );
    }
}
