<?php
namespace App\ServiceModules\ChargeWallet;

use App\Models\Purchase;
use App\Models\Service;
use App\Payment\BoughtServiceService;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\ServiceModules\ServiceModule;
use App\System\Auth;
use App\System\Heart;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\View\Interfaces\IBeLoggedMust;

class ServiceChargeWallet extends ServiceModule implements
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

    public function purchaseFormValidate($data)
    {
        if (!$this->auth->check()) {
            return [
                'status' => "no_access",
                'text' => $this->lang->t('not_logged_or_no_perm'),
                'positive' => false,
            ];
        }

        // Są tylko dwie metody doładowania portfela
        if (!in_array($data['method'], [Purchase::METHOD_SMS, Purchase::METHOD_TRANSFER])) {
            return [
                'status' => "wrong_method",
                'text' => $this->lang->t('wrong_charge_method'),
                'positive' => false,
            ];
        }

        $warnings = [];

        if ($data['method'] == Purchase::METHOD_SMS) {
            if (!strlen($data['tariff'])) {
                $warnings['tariff'][] = $this->lang->t('charge_amount_not_chosen');
            }
        } else {
            if ($data['method'] == Purchase::METHOD_TRANSFER) {
                // Kwota doładowania
                if ($warning = check_for_warnings("number", $data['transfer_amount'])) {
                    $warnings['transfer_amount'] = array_merge(
                        (array) $warnings['transfer_amount'],
                        $warning
                    );
                }
                if ($data['transfer_amount'] <= 1) {
                    $warnings['transfer_amount'][] = $this->lang->t(
                        'charge_amount_too_low',
                        "1.00 " . $this->settings->getCurrency()
                    );
                }
            }
        }

        // Jeżeli są jakieś błedy, to je zwróć
        if (!empty($warnings)) {
            return [
                'status' => "warnings",
                'text' => $this->lang->t('form_wrong_filled'),
                'positive' => false,
                'data' => ['warnings' => $warnings],
            ];
        }

        $purchase = new Purchase($this->auth->user());
        $purchase->setService($this->service->getId());
        $purchase->setTariff($this->heart->getTariff($data['tariff']));
        $purchase->setPayment([
            'no_wallet' => true,
        ]);

        if ($data['method'] == Purchase::METHOD_SMS) {
            $purchase->setPayment([
                'no_transfer' => true,
            ]);
            $purchase->setOrder([
                'amount' => $this->heart->getTariff($data['tariff'])->getProvision(),
            ]);
        } elseif ($data['method'] == Purchase::METHOD_TRANSFER) {
            $purchase->setPayment([
                'cost' => $data['transfer_amount'] * 100,
                'no_sms' => true,
            ]);
            $purchase->setOrder([
                'amount' => $data['transfer_amount'] * 100,
            ]);
        }

        return [
            'status' => "ok",
            'text' => $this->lang->t('purchase_form_validated'),
            'positive' => true,
            'purchase_data' => $purchase,
        ];
    }

    public function orderDetails(Purchase $purchaseData)
    {
        $amount = number_format($purchaseData->getOrder('amount') / 100, 2);

        return $this->template->render(
            "services/charge_wallet/order_details",
            compact('amount'),
            true,
            false
        );
    }

    public function purchase(Purchase $purchaseData)
    {
        // Aktualizacja stanu portfela
        $this->chargeWallet($purchaseData->user->getUid(), $purchaseData->getOrder('amount'));

        return $this->boughtServiceService->create(
            $purchaseData->user->getUid(),
            $purchaseData->user->getUsername(),
            $purchaseData->user->getLastIp(),
            $purchaseData->getPayment('method'),
            $purchaseData->getPayment('payment_id'),
            $this->service->getId(),
            0,
            number_format($purchaseData->getOrder('amount') / 100, 2),
            $purchaseData->user->getUsername(),
            $purchaseData->getEmail()
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
