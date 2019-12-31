<?php
namespace App\Payment;

use App\Models\Purchase;
use App\System\Heart;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Abstracts\SupportTransfer;
use UnexpectedValueException;

class PaymentService
{
    /** @var Heart */
    private $heart;

    /** @var Settings */
    private $settings;

    /** @var Translator */
    private $lang;

    /** @var TransferPaymentService */
    private $transferPaymentService;

    /** @var SmsPaymentService */
    private $smsPaymentService;

    /** @var ServiceCodePaymentService */
    private $serviceCodePaymentService;

    /** @var WalletPaymentService */
    private $walletPaymentService;

    public function __construct(
        Heart $heart,
        TranslationManager $translationManager,
        Settings $settings,
        TransferPaymentService $transferPaymentService,
        SmsPaymentService $smsPaymentService,
        ServiceCodePaymentService $serviceCodePaymentService,
        WalletPaymentService $walletPaymentService
    ) {
        $this->heart = $heart;
        $this->settings = $settings;
        $this->lang = $translationManager->user();
        $this->transferPaymentService = $transferPaymentService;
        $this->smsPaymentService = $smsPaymentService;
        $this->serviceCodePaymentService = $serviceCodePaymentService;
        $this->walletPaymentService = $walletPaymentService;
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
        $paymentModule = null;
        if ($purchase->getPayment('method') == Purchase::METHOD_SMS) {
            $paymentPlatformId =
                $purchase->getPayment('sms_platform') ?: $this->settings['sms_platform'];
            $paymentModule = $this->heart->getPaymentModuleByPlatformIdOrFail($paymentPlatformId);
        } elseif ($purchase->getPayment('method') == Purchase::METHOD_TRANSFER) {
            $paymentPlatformId =
                $purchase->getPayment('transfer_platform') ?: $this->settings['transfer_platform'];
            $paymentModule = $this->heart->getPaymentModuleByPlatformIdOrFail($paymentPlatformId);
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
            !($paymentModule instanceof SupportSms)
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
            !($paymentModule instanceof SupportTransfer)
        ) {
            return [
                'status' => "transfer_unavailable",
                'text' => $this->lang->translate('transfer_unavailable'),
                'positive' => false,
            ];
        }

        $paymentId = null;

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
            $result = $this->smsPaymentService->payWithSms(
                $paymentModule,
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
            $paymentId = $this->walletPaymentService->payWithWallet(
                $purchase->getPayment('cost'),
                $purchase->user
            );

            // Metoda pay_wallet zwróciła błąd.
            if (is_array($paymentId)) {
                return $paymentId;
            }
        }

        if ($purchase->getPayment('method') === Purchase::METHOD_SERVICE_CODE) {
            // Dodanie informacji o płatności z portfela
            $paymentId = $this->serviceCodePaymentService->payWithServiceCode(
                $purchase,
                $serviceModule
            );

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
                    $serviceModule->service->getName()
                )
            );

            return $this->transferPaymentService->payWithTransfer($paymentModule, $purchase);
        }

        throw new UnexpectedValueException();
    }
}
