<?php

use App\Heart;
use App\Payment;
use App\Settings;
use App\Translator;

$heart->register_service_module("charge_wallet", "Doładowanie Portfela", "ServiceChargeWallet",
    "ServiceChargeWalletSimple");

class ServiceChargeWalletSimple extends Service implements I_BeLoggedMust
{
    const MODULE_ID = "charge_wallet";
}

class ServiceChargeWallet extends ServiceChargeWalletSimple implements IService_Purchase, IService_PurchaseWeb
{
    /** @var Heart */
    protected $heart;

    /** @var Translator */
    protected $lang;

    /** @var Settings */
    protected $settings;

    public function __construct($service = null)
    {
        parent::__construct($service);

        $this->lang = app()->make(Translator::class);
        $this->heart = app()->make(Heart::class);
        $this->settings = app()->make(Settings::class);
    }

    public function purchase_form_get()
    {
        $settings = $this->settings;
        $lang = $this->lang;

        if (strlen($this->settings['sms_service'])) {
            $payment_sms = new Payment($this->settings['sms_service']);

            // Pobieramy opcję wyboru doładowania za pomocą SMS
            $option_sms = eval($this->template->render("services/" . $this::MODULE_ID . "/option_sms"));

            $sms_list = "";
            foreach ($payment_sms->getPaymentModule()->getTariffs() AS $tariff) {
                $provision = number_format($tariff->getProvision() / 100.0, 2);
                // Przygotowuje opcje wyboru
                $sms_list .= create_dom_element("option",
                    $this->lang->sprintf($this->lang->translate('charge_sms_option'), $tariff->getSmsCostBrutto(),
                        $this->settings['currency'], $provision, $this->settings['currency']),
                    [
                        'value' => $tariff->getId(),
                    ]
                );
            }

            $sms_body = eval($this->template->render("services/" . $this::MODULE_ID . "/sms_body"));
        }

        if (strlen($this->settings['transfer_service'])) {
            // Pobieramy opcję wyboru doładowania za pomocą przelewu
            $option_transfer = eval($this->template->render("services/" . $this::MODULE_ID . "/option_transfer"));

            $transfer_body = eval($this->template->render("services/" . $this::MODULE_ID . "/transfer_body"));
        }

        return eval($this->template->render("services/" . $this::MODULE_ID . "/purchase_form"));
    }

    public function purchase_form_validate($data)
    {
        if (!is_logged()) {
            return [
                'status'   => "not_logged_in",
                'text'     => $this->lang->translate('you_arent_logged'),
                'positive' => false,
            ];
        }

        // Są tylko dwie metody doładowania portfela
        if (!in_array($data['method'], ["sms", "transfer"])) {
            return [
                'status'   => "wrong_method",
                'text'     => $this->lang->translate('wrong_charge_method'),
                'positive' => false,
            ];
        }

        $warnings = [];

        if ($data['method'] == "sms") {
            if (!strlen($data['tariff'])) {
                $warnings['tariff'][] = $this->lang->translate('charge_amount_not_chosen');
            }
        } else {
            if ($data['method'] == "transfer") {
                // Kwota doładowania
                if ($warning = check_for_warnings("number", $data['transfer_amount'])) {
                    $warnings['transfer_amount'] = array_merge((array)$warnings['transfer_amount'], $warning);
                }
                if ($data['transfer_amount'] <= 1) {
                    $warnings['transfer_amount'][] = $this->lang->sprintf($this->lang->translate('charge_amount_too_low'),
                        "1.00 " . $this->settings['currency']);
                }
            }
        }

        // Jeżeli są jakieś błedy, to je zwróć
        if (!empty($warnings)) {
            return [
                'status'   => "warnings",
                'text'     => $this->lang->translate('form_wrong_filled'),
                'positive' => false,
                'data'     => ['warnings' => $warnings],
            ];
        }

        $purchase_data = new Entity_Purchase();
        $purchase_data->setService($this->service['id']);
        $purchase_data->setTariff($this->heart->getTariff($data['tariff']));
        $purchase_data->setPayment([
            'no_wallet' => true,
        ]);

        if ($data['method'] == "sms") {
            $purchase_data->setPayment([
                'no_transfer' => true,
            ]);
            $purchase_data->setOrder([
                'amount' => $this->heart->getTariff($data['tariff'])->getProvision(),
            ]);
        } elseif ($data['method'] == "transfer") {
            $purchase_data->setPayment([
                'cost'   => $data['transfer_amount'] * 100,
                'no_sms' => true,
            ]);
            $purchase_data->setOrder([
                'amount' => $data['transfer_amount'] * 100,
            ]);
        }

        return [
            'status'        => "ok",
            'text'          => $this->lang->translate('purchase_form_validated'),
            'positive'      => true,
            'purchase_data' => $purchase_data,
        ];
    }

    public function order_details($purchase_data)
    {
        $lang = $this->lang;
        $settings = $this->settings;

        $amount = number_format($purchase_data->getOrder('amount') / 100, 2);

        return eval($this->template->render("services/" . $this::MODULE_ID . "/order_details", true, false));
    }

    public function purchase($purchase_data)
    {
        // Aktualizacja stanu portfela
        $this->charge_wallet($purchase_data->user->getUid(), $purchase_data->getOrder('amount'));

        return add_bought_service_info(
            $purchase_data->user->getUid(), $purchase_data->user->getUsername(), $purchase_data->user->getLastip(),
            $purchase_data->getPayment('method'), $purchase_data->getPayment('payment_id'), $this->service['id'], 0,
            number_format($purchase_data->getOrder('amount') / 100, 2), $purchase_data->user->getUsername(),
            $purchase_data->getEmail()
        );
    }

    public function purchase_info($action, $data)
    {
        $heart = $this->heart;
        $settings = $this->settings;
        $lang = $this->lang;

        $data['amount'] .= ' ' . $this->settings['currency'];
        $data['cost'] = number_format($data['cost'] / 100, 2) . ' ' . $this->settings['currency'];

        if ($data['payment'] == "sms") {
            $data['sms_code'] = htmlspecialchars($data['sms_code']);
            $data['sms_text'] = htmlspecialchars($data['sms_text']);
            $data['sms_number'] = htmlspecialchars($data['sms_number']);
        }

        if ($action == "web") {
            if ($data['payment'] == "sms") {
                $desc = $this->lang->sprintf($this->lang->translate('wallet_was_charged'), $data['amount']);
                return eval($this->template->render("services/" . $this::MODULE_ID . "/web_purchase_info_sms", true,
                    false));
            } elseif ($data['payment'] == "transfer") {
                return eval($this->template->render("services/" . $this::MODULE_ID . "/web_purchase_info_transfer",
                    true, false));
            }

            return;
        }

        if ($action == "payment_log") {
            return [
                'text'  => $this->lang->sprintf($this->lang->translate('wallet_was_charged'), $data['amount']),
                'class' => "income",
            ];
        }
    }

    public function description_short_get()
    {
        return $this->service['description'];
    }

    /**
     * @param int $uid
     * @param int $amount
     */
    private function charge_wallet($uid, $amount)
    {
        $this->db->query($this->db->prepare(
            "UPDATE `" . TABLE_PREFIX . "users` " .
            "SET `wallet` = `wallet` + '%d' " .
            "WHERE `uid` = '%d'",
            [$amount, $uid]
        ));
    }
}