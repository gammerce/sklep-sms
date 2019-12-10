<?php
namespace App\Payment;

use App\Models\Purchase;
use App\Models\User;
use App\Payment;
use App\Services\Service;
use App\System\Database;
use App\System\Heart;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use UnexpectedValueException;

class PaymentService
{
    /** @var Heart */
    private $heart;

    /** @var Settings */
    private $settings;

    /** @var Translator */
    private $lang;

    /** @var Translator */
    private $langShop;

    /** @var Database */
    private $db;

    public function __construct(
        Heart $heart,
        TranslationManager $translationManager,
        Settings $settings,
        Database $db
    ) {
        $this->heart = $heart;
        $this->settings = $settings;
        $this->lang = $translationManager->user();
        $this->langShop = $translationManager->shop();
        $this->db = $db;
    }

    public function makePayment(Purchase $purchase)
    {
        $warnings = [];

        // Tworzymy obiekt usługi którą kupujemy
        if (($serviceModule = $this->heart->getServiceModule($purchase->getService())) === null) {
            return [
                'status' => "wrong_module",
                'text' => $this->lang->translate('bad_module'),
                'positive' => false,
            ];
        }

        if (
            !in_array($purchase->getPayment('method'), [
                Purchase::METHOD_SMS,
                Purchase::METHOD_TRANSFER,
                Purchase::METHOD_WALLET,
                Purchase::METHOD_SERVICE_CODE,
            ])
        ) {
            return [
                'status' => "wrong_method",
                'text' => $this->lang->translate('wrong_payment_method'),
                'positive' => false,
            ];
        }

        // Tworzymy obiekt, który będzie nam obsługiwał proces płatności
        if ($purchase->getPayment('method') == Purchase::METHOD_SMS) {
            $transactionService = if_strlen2(
                $purchase->getPayment('sms_service'),
                $this->settings['sms_service']
            );
            $payment = new Payment($transactionService);
        } elseif ($purchase->getPayment('method') == Purchase::METHOD_TRANSFER) {
            $transactionService = if_strlen2(
                $purchase->getPayment('transfer_service'),
                $this->settings['transfer_service']
            );
            $payment = new Payment($transactionService);
        }

        // Pobieramy ile kosztuje ta usługa dla przelewu / portfela
        if ($purchase->getPayment('cost') === null) {
            $purchase->setPayment([
                'cost' => $purchase->getTariff()->getProvision(),
            ]);
        }

        // Metoda płatności
        if (
            $purchase->getPayment('method') == Purchase::METHOD_WALLET &&
            !$purchase->user->exists()
        ) {
            return [
                'status' => "wallet_not_logged",
                'text' => $this->lang->translate('no_login_no_wallet'),
                'positive' => false,
            ];
        }

        if (
            $purchase->getPayment('method') == Purchase::METHOD_SMS &&
            !$payment->getPaymentModule()->supportSms()
        ) {
            return [
                'status' => "sms_unavailable",
                'text' => $this->lang->translate('sms_unavailable'),
                'positive' => false,
            ];
        }

        if (
            $purchase->getPayment('method') == Purchase::METHOD_SMS &&
            $purchase->getTariff() === null
        ) {
            return [
                'status' => "no_sms_option",
                'text' => $this->lang->translate('no_sms_payment'),
                'positive' => false,
            ];
        }

        if (
            $purchase->getPayment('method') == Purchase::METHOD_TRANSFER &&
            $purchase->getPayment('cost') <= 1
        ) {
            return [
                'status' => "too_little_for_transfer",
                'text' => $this->lang->sprintf(
                    $this->lang->translate('transfer_above_amount'),
                    $this->settings['currency']
                ),
                'positive' => false,
            ];
        }

        if (
            $purchase->getPayment('method') == Purchase::METHOD_TRANSFER &&
            !$payment->getPaymentModule()->supportTransfer()
        ) {
            return [
                'status' => "transfer_unavailable",
                'text' => $this->lang->translate('transfer_unavailable'),
                'positive' => false,
            ];
        }

        // Kod SMS
        $purchase->setPayment([
            'sms_code' => trim($purchase->getPayment('sms_code')),
        ]);

        if (
            $purchase->getPayment('method') == Purchase::METHOD_SMS &&
            ($warning = check_for_warnings("sms_code", $purchase->getPayment('sms_code')))
        ) {
            $warnings['sms_code'] = array_merge((array) $warnings['sms_code'], $warning);
        }

        // Kod na usługę
        if (
            $purchase->getPayment('method') == Purchase::METHOD_SERVICE_CODE &&
            !strlen($purchase->getPayment('service_code'))
        ) {
            $warnings['service_code'][] = $this->lang->translate('field_no_empty');
        }

        if ($warnings) {
            $warningData = [];
            $warningData['warnings'] = format_warnings($warnings);

            return [
                'status' => "warnings",
                'text' => $this->lang->translate('form_wrong_filled'),
                'positive' => false,
                'data' => $warningData,
            ];
        }

        if ($purchase->getPayment('method') === Purchase::METHOD_SMS) {
            // Sprawdzamy kod zwrotny
            $result = $payment->paySms(
                $purchase->getPayment('sms_code'),
                $purchase->getTariff(),
                $purchase->user
            );
            $paymentId = $result['payment_id'];

            if ($result['status'] !== 'ok') {
                return [
                    'status' => $result['status'],
                    'text' => $result['text'],
                    'positive' => false,
                ];
            }
        }

        if ($purchase->getPayment('method') === Purchase::METHOD_WALLET) {
            // Dodanie informacji o płatności z portfela
            $paymentId = $this->payWallet($purchase->getPayment('cost'), $purchase->user);

            // Metoda pay_wallet zwróciła błąd.
            if (is_array($paymentId)) {
                return $paymentId;
            }
        }

        if ($purchase->getPayment('method') === Purchase::METHOD_SERVICE_CODE) {
            // Dodanie informacji o płatności z portfela
            $paymentId = $this->payServiceCode($purchase, $serviceModule);

            // Funkcja pay_service_code zwróciła błąd.
            if (is_array($paymentId)) {
                return $paymentId;
            }
        }

        if (
            in_array($purchase->getPayment('method'), [
                Purchase::METHOD_WALLET,
                Purchase::METHOD_SMS,
                Purchase::METHOD_SERVICE_CODE,
            ])
        ) {
            // Dokonujemy zakupu usługi
            $purchase->setPayment([
                'payment_id' => $paymentId,
            ]);
            $boughtServiceId = $serviceModule->purchase($purchase);

            return [
                'status' => "purchased",
                'text' => $this->lang->translate('purchase_success'),
                'positive' => true,
                'data' => ['bsid' => $boughtServiceId],
            ];
        }

        if ($purchase->getPayment('method') == Purchase::METHOD_TRANSFER) {
            $purchase->setDesc(
                $this->lang->sprintf(
                    $this->lang->translate('payment_for_service'),
                    $serviceModule->service['name']
                )
            );

            return $payment->payTransfer($purchase);
        }

        throw new UnexpectedValueException();
    }

    /**
     * @param int $cost
     * @param User $user
     * @return array|int|string
     */
    private function payWallet($cost, $user)
    {
        // Sprawdzanie, czy jest wystarczająca ilość kasy w portfelu
        if ($cost > $user->getWallet()) {
            return [
                'status' => "no_money",
                'text' => $this->lang->translate('not_enough_money'),
                'positive' => false,
            ];
        }

        // Zabieramy kasę z portfela
        $this->chargeWallet($user->getUid(), -$cost);

        // Dodajemy informacje o płatności portfelem
        $this->db->query(
            $this->db->prepare(
                "INSERT INTO `" .
                    TABLE_PREFIX .
                    "payment_wallet` " .
                    "SET `cost` = '%d', `ip` = '%s', `platform` = '%s'",
                [$cost, $user->getLastIp(), $user->getPlatform()]
            )
        );

        return $this->db->lastId();
    }

    /**
     * @param Purchase $purchaseData
     * @param Service $serviceModule
     *
     * @return array|int|string
     */
    private function payServiceCode(Purchase $purchaseData, $serviceModule)
    {
        $result = $this->db->query(
            $this->db->prepare(
                "SELECT * FROM `" .
                    TABLE_PREFIX .
                    "service_codes` " .
                    "WHERE `code` = '%s' " .
                    "AND `service` = '%s' " .
                    "AND (`server` = '0' OR `server` = '%s') " .
                    "AND (`tariff` = '0' OR `tariff` = '%d') " .
                    "AND (`uid` = '0' OR `uid` = '%s')",
                [
                    $purchaseData->getPayment('service_code'),
                    $purchaseData->getService(),
                    $purchaseData->getOrder('server'),
                    $purchaseData->getTariff(),
                    $purchaseData->user->getUid(),
                ]
            )
        );

        while ($row = $this->db->fetchArrayAssoc($result)) {
            if ($serviceModule->serviceCodeValidate($purchaseData, $row)) {
                // Znalezlismy odpowiedni kod
                $this->db->query(
                    $this->db->prepare(
                        "DELETE FROM `" . TABLE_PREFIX . "service_codes` " . "WHERE `id` = '%d'",
                        [$row['id']]
                    )
                );

                // Dodajemy informacje o płatności kodem
                $this->db->query(
                    $this->db->prepare(
                        "INSERT INTO `" .
                            TABLE_PREFIX .
                            "payment_code` " .
                            "SET `code` = '%s', `ip` = '%s', `platform` = '%s'",
                        [
                            $purchaseData->getPayment('service_code'),
                            $purchaseData->user->getLastIp(),
                            $purchaseData->user->getPlatform(),
                        ]
                    )
                );
                $paymentId = $this->db->lastId();

                log_info(
                    $this->langShop->sprintf(
                        $this->langShop->translate('purchase_code'),
                        $purchaseData->getPayment('service_code'),
                        $purchaseData->user->getUsername(),
                        $purchaseData->user->getUid(),
                        $paymentId
                    )
                );

                return $paymentId;
            }
        }

        return [
            'status' => "wrong_service_code",
            'text' => $this->lang->translate('bad_service_code'),
            'positive' => false,
        ];
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
