<?php
namespace App;

use Entity_Purchase;
use Entity_Tariff;
use Entity_TransferFinalize;
use Entity_User;
use IPayment_Sms;
use IPayment_Transfer;

class Payment
{
    const SMS_NOT_SUPPORTED = 'sms_not_supported';
    const TRANSFER_NOT_SUPPORTED = 'transfer_not_supported';

    /** @var PaymentModule|IPayment_Sms|IPayment_Transfer */
    private $payment_module = null;

    function __construct($payment_module_id)
    {
        global $heart, $lang;

        // Tworzymy obiekt obslugujacy stricte weryfikacje
        $className = $heart->get_payment_module($payment_module_id);
        if ($className !== null) {
            $this->payment_module = app()->make($className);
        }

        // API podanej usługi nie istnieje.
        if ($this->payment_module === null) {
            output_page($lang->sprintf($lang->translate('payment_bad_service'), $payment_module_id));
        }
    }

    /**
     * @param string        $sms_code
     * @param Entity_Tariff $tariff
     * @param Entity_User   $user
     *
     * @return array
     */
    public function pay_sms($sms_code, $tariff, $user)
    {
        global $db, $settings, $lang, $lang_shop;

        if (!$this->getPaymentModule()->supportSms()) {
            return [
                'status' => Payment::SMS_NOT_SUPPORTED,
                'text'   => $lang->translate('sms_info_' . Payment::SMS_NOT_SUPPORTED),
            ];
        }

        if (object_implements($this->getPaymentModule(), "IPayment_Sms")) {
            $sms_number = $tariff->getNumber();
            $sms_return = $this->getPaymentModule()->verify_sms($sms_code, $sms_number);

            if (!is_array($sms_return)) {
                $sms_return = [
                    'status' => $sms_return,
                ];
            }
        } else {
            $sms_return['status'] = Payment::SMS_NOT_SUPPORTED;
            // Nie przerywamy jeszcze, bo chcemy sprawdzic czy nie ma takiego SMSa do wykorzystania w bazie
        }

        // Jezeli weryfikacja smsa nie zwrocila, ze kod zostal prawidlowo zweryfikowany
        // ani, że sms został wysłany na błędny numer,
        // to sprawdzamy czy kod jest w bazie kodów do wykorzystania
        if (!isset($sms_return) || !in_array($sms_return['status'], [IPayment_Sms::BAD_NUMBER, IPayment_Sms::OK])) {
            $result = $db->query($db->prepare(
                "SELECT * FROM `" . TABLE_PREFIX . "sms_codes` " .
                "WHERE `code` = '%s' AND `tariff` = '%d'",
                [$sms_code, $tariff->getId()]
            ));

            // Jest taki kod w bazie
            if ($db->num_rows($result)) {
                $db_code = $db->fetch_array_assoc($result);

                // Usuwamy kod z listy kodow do wykorzystania
                $db->query($db->prepare(
                    "DELETE FROM `" . TABLE_PREFIX . "sms_codes` " .
                    "WHERE `id` = '%d'",
                    [$db_code['id']]
                ));
                // Ustawienie wartości, jakby kod był prawidłowy
                $sms_return['status'] = IPayment_Sms::OK;

                log_info($lang_shop->sprintf($lang_shop->translate('payment_remove_code_from_db'), $db_code['code'],
                    $db_code['tariff']));
            }
        }

        if ($sms_return['status'] == IPayment_Sms::OK) {
            // Dodanie informacji o płatności sms
            $db->query($db->prepare(
                "INSERT INTO `" . TABLE_PREFIX . "payment_sms` (`code`, `income`, `cost`, `text`, `number`, `ip`, `platform`, `free`) " .
                "VALUES ('%s','%d','%d','%s','%s','%s','%s','%d')",
                [
                    $sms_code,
                    get_sms_cost($sms_number) / 2,
                    ceil(get_sms_cost($sms_number) * $settings['vat']),
                    $this->getPaymentModule()->getSmsCode(),
                    $sms_number,
                    $user->getLastIp(),
                    $user->getPlatform(),
                    $sms_return['free'] ? 1 : $db_code['free'],
                ]
            ));

            $payment_id = $db->last_id();
        } // SMS został wysłany na błędny numer
        else {
            if ($sms_return['status'] == IPayment_Sms::BAD_NUMBER && isset($sms_return['tariff'])) {
                // Dodajemy kod do listy kodów do wykorzystania
                $db->query($db->prepare(
                    "INSERT INTO `" . TABLE_PREFIX . "sms_codes` " .
                    "SET `code` = '%s', `tariff` = '%d', `free` = '0'",
                    [$sms_code, $sms_return['tariff']]
                ));

                log_info($lang_shop->sprintf($lang_shop->translate('add_code_to_reuse'), $sms_code,
                    $sms_return['tariff'],
                    $user->getUsername(), $user->getUid(), $user->getLastIp(), $tariff->getId()));
            } else {
                if ($sms_return['status'] != Payment::SMS_NOT_SUPPORTED) {
                    log_info($lang_shop->sprintf($lang_shop->translate('bad_sms_code_used'), $user->getUsername(),
                        $user->getUid(), $user->getLastIp(),
                        $sms_code, $this->getPaymentModule()->getSmsCode(), $sms_number, $sms_return['status']));
                }
            }
        }

        return [
            'status'     => $sms_return['status'],
            'text'       => if_strlen2($sms_return['text'],
                if_strlen2($lang->translate('sms_info_' . $sms_return['status']), $sms_return['status'])),
            'payment_id' => $payment_id,
        ];
    }

    /**
     * @param Entity_Purchase $purchase_data
     *
     * @return array
     */
    public function pay_transfer($purchase_data)
    {
        global $lang;

        if (!$this->getPaymentModule()->supportTransfer() || !object_implements($this->getPaymentModule(),
                "IPayment_Transfer")) {
            return [
                'status' => Payment::TRANSFER_NOT_SUPPORTED,
                'text'   => $lang->translate('transfer_' . Payment::TRANSFER_NOT_SUPPORTED),
            ];
        }

        $serialized = serialize($purchase_data);
        $data_filename = time() . "-" . md5($serialized);
        file_put_contents(SCRIPT_ROOT . "data/transfers/" . $data_filename, $serialized);

        return [
            'status'   => "transfer",
            'text'     => $lang->translate('transfer_prepared'),
            'positive' => true,
            'data'     => ['data' => $this->getPaymentModule()->prepare_transfer($purchase_data, $data_filename)]
            // Przygotowuje dane płatności transferem
        ];
    }

    /**
     * @param Entity_TransferFinalize $transfer_finalize
     *
     * @return bool
     */
    public function transferFinalize($transfer_finalize)
    {
        global $heart, $db, $lang_shop;

        $result = $db->query($db->prepare(
            "SELECT * FROM `" . TABLE_PREFIX . "payment_transfer` " .
            "WHERE `id` = '%d'",
            [$transfer_finalize->getOrderid()]
        ));

        // Próba ponownej autoryzacji
        if ($db->num_rows($result)) {
            return false;
        }

        // Nie znaleziono pliku z danymi
        if (!$transfer_finalize->getDataFilename() || !file_exists(SCRIPT_ROOT . "data/transfers/" . $transfer_finalize->getDataFilename())) {
            log_info($lang_shop->sprintf($lang_shop->translate('transfer_no_data_file'),
                $transfer_finalize->getOrderid()));

            return false;
        }

        /** @var Entity_Purchase $purchase_data */
        $purchase_data = unserialize(file_get_contents(SCRIPT_ROOT . "data/transfers/" . $transfer_finalize->getDataFilename()));

        // Fix: get user data again to avoid bugs linked with user wallet
        $purchase_data->user = $heart->get_user($purchase_data->user->getUid());

        // Dodanie informacji do bazy danych
        $db->query($db->prepare(
            "INSERT INTO `" . TABLE_PREFIX . "payment_transfer` " .
            "SET `id` = '%s', `income` = '%d', `transfer_service` = '%s', `ip` = '%s', `platform` = '%s' ",
            [
                $transfer_finalize->getOrderid(),
                $purchase_data->getPayment('cost'),
                $transfer_finalize->getTransferService(),
                $purchase_data->user->getLastIp(),
                $purchase_data->user->getPlatform(),
            ]
        ));
        unlink(SCRIPT_ROOT . "data/transfers/" . $transfer_finalize->getDataFilename());

        // Błędny moduł
        if (($service_module = $heart->get_service_module($purchase_data->getService())) === null) {
            log_info($lang_shop->sprintf($lang_shop->translate('transfer_bad_module'), $transfer_finalize->getOrderid(),
                $purchase_data->getService()));

            return false;
        }

        if (!object_implements($service_module, "IService_Purchase")) {
            log_info($lang_shop->sprintf($lang_shop->translate('transfer_no_purchase'),
                $transfer_finalize->getOrderid(), $purchase_data->getService()));

            return false;
        }

        // Dokonujemy zakupu
        $purchase_data->setPayment([
            'method'     => 'transfer',
            'payment_id' => $transfer_finalize->getOrderid(),
        ]);
        $bought_service_id = $service_module->purchase($purchase_data);

        log_info($lang_shop->sprintf($lang_shop->translate('payment_transfer_accepted'), $bought_service_id,
            $transfer_finalize->getOrderid(), $transfer_finalize->getAmount(),
            $transfer_finalize->getTransferService(), $purchase_data->user->getUsername(),
            $purchase_data->user->getUid(), $purchase_data->user->getLastIp()));

        return true;
    }

    /**
     * @param bool $escape
     *
     * @return string
     */
    public function getSmsCode($escape = false)
    {
        return $escape ? htmlspecialchars($this->getPaymentModule()->getSmsCode()) : $this->getPaymentModule()->getSmsCode();
    }

    /**
     * @return IPayment_Sms|IPayment_Transfer|PaymentModule
     */
    public function getPaymentModule()
    {
        return $this->payment_module;
    }

}