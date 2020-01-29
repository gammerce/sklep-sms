<?php
namespace App\Payment;

use App\Http\Validation\Rules\MaxLengthRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Validator;
use App\Models\Purchase;
use App\Services\SmsPriceService;
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

    /** @var SmsPriceService */
    private $smsPriceService;

    public function __construct(
        Heart $heart,
        TranslationManager $translationManager,
        Settings $settings,
        TransferPaymentService $transferPaymentService,
        SmsPaymentService $smsPaymentService,
        SmsPriceService $smsPriceService,
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
        $this->smsPriceService = $smsPriceService;
    }

    public function makePayment(Purchase $purchase)
    {
        $serviceModule = $this->heart->getServiceModule($purchase->getService());

        if (!$serviceModule) {
            return [
                'status' => "wrong_module",
                'text' => $this->lang->t('bad_module'),
                'positive' => false,
            ];
        }

        if (
            !in_array($purchase->getPayment(Purchase::PAYMENT_METHOD), [
                Purchase::METHOD_SMS,
                Purchase::METHOD_TRANSFER,
                Purchase::METHOD_WALLET,
                Purchase::METHOD_SERVICE_CODE,
            ])
        ) {
            return [
                'status' => "wrong_method",
                'text' => $this->lang->t('wrong_payment_method'),
                'positive' => false,
            ];
        }

        $paymentModule = null;
        if ($purchase->getPayment(Purchase::PAYMENT_METHOD) === Purchase::METHOD_SMS) {
            $paymentModule = $this->heart->getPaymentModuleByPlatformId(
                $purchase->getPayment(Purchase::PAYMENT_SMS_PLATFORM)
            );
        } elseif ($purchase->getPayment(Purchase::PAYMENT_METHOD) === Purchase::METHOD_TRANSFER) {
            $paymentModule = $this->heart->getPaymentModuleByPlatformId(
                $purchase->getPayment(Purchase::PAYMENT_TRANSFER_PLATFORM)
            );
        }

        // Metoda płatności
        if (
            $purchase->getPayment(Purchase::PAYMENT_METHOD) == Purchase::METHOD_WALLET &&
            !$purchase->user->exists()
        ) {
            return [
                'status' => "wallet_not_logged",
                'text' => $this->lang->t('no_login_no_wallet'),
                'positive' => false,
            ];
        }

        if (
            $purchase->getPayment(Purchase::PAYMENT_METHOD) == Purchase::METHOD_SMS &&
            !($paymentModule instanceof SupportSms)
        ) {
            return [
                'status' => "sms_unavailable",
                'text' => $this->lang->t('sms_unavailable'),
                'positive' => false,
            ];
        }

        if (
            $purchase->getPayment(Purchase::PAYMENT_METHOD) == Purchase::METHOD_SMS &&
            $purchase->getPayment(Purchase::PAYMENT_SMS_PRICE) === null
        ) {
            return [
                'status' => "no_sms_price",
                'text' => $this->lang->t('payment_method_unavailable'),
                'positive' => false,
            ];
        }

        if (
            in_array($purchase->getPayment(Purchase::PAYMENT_METHOD), [
                Purchase::METHOD_TRANSFER,
                Purchase::METHOD_WALLET,
            ]) &&
            $purchase->getPayment(Purchase::PAYMENT_TRANSFER_PRICE) === null
        ) {
            return [
                'status' => "no_transfer_price",
                'text' => $this->lang->t('payment_method_unavailable'),
                'positive' => false,
            ];
        }

        if (
            $purchase->getPayment(Purchase::PAYMENT_METHOD) == Purchase::METHOD_TRANSFER &&
            $purchase->getPayment(Purchase::PAYMENT_TRANSFER_PRICE) <= 100
        ) {
            return [
                'status' => "too_little_for_transfer",
                'text' => $this->lang->t('transfer_above_amount', $this->settings->getCurrency()),
                'positive' => false,
            ];
        }

        if (
            $purchase->getPayment(Purchase::PAYMENT_METHOD) == Purchase::METHOD_TRANSFER &&
            !($paymentModule instanceof SupportTransfer)
        ) {
            return [
                'status' => "transfer_unavailable",
                'text' => $this->lang->t('transfer_unavailable'),
                'positive' => false,
            ];
        }

        $paymentId = null;

        $validator = new Validator(
            [
                'service_code' => $purchase->getPayment(Purchase::PAYMENT_SERVICE_CODE),
                'sms_code' => $purchase->getPayment(Purchase::PAYMENT_SMS_CODE),
            ],
            [
                'service_code' => [
                    $purchase->getPayment(Purchase::PAYMENT_METHOD) ===
                    Purchase::METHOD_SERVICE_CODE
                        ? new RequiredRule()
                        : null,
                ],
                'sms_code' => [
                    $purchase->getPayment(Purchase::PAYMENT_METHOD) === Purchase::METHOD_SMS
                        ? new RequiredRule()
                        : null,
                    new MaxLengthRule(16),
                ],
            ]
        );
        $validator->validateOrFail();

        if ($purchase->getPayment(Purchase::PAYMENT_METHOD) === Purchase::METHOD_SMS) {
            // Let's check sms code
            $result = $this->smsPaymentService->payWithSms(
                $paymentModule,
                $purchase->getPayment(Purchase::PAYMENT_SMS_CODE),
                $this->smsPriceService->getNumber(
                    $purchase->getPayment(Purchase::PAYMENT_SMS_PRICE),
                    $paymentModule
                ),
                $purchase->user
            );

            if ($result['status'] !== 'ok') {
                return [
                    'status' => $result['status'],
                    'text' => $result['text'],
                    'positive' => false,
                ];
            }

            $paymentId = $result['payment_id'];
        }

        if ($purchase->getPayment(Purchase::PAYMENT_METHOD) === Purchase::METHOD_WALLET) {
            $paymentId = $this->walletPaymentService->payWithWallet(
                $purchase->getPayment(Purchase::PAYMENT_TRANSFER_PRICE),
                $purchase->user
            );

            if (is_array($paymentId)) {
                return $paymentId;
            }
        }

        if ($purchase->getPayment(Purchase::PAYMENT_METHOD) === Purchase::METHOD_SERVICE_CODE) {
            $paymentId = $this->serviceCodePaymentService->payWithServiceCode($purchase);

            if (is_array($paymentId)) {
                return $paymentId;
            }
        }

        if (
            in_array($purchase->getPayment(Purchase::PAYMENT_METHOD), [
                Purchase::METHOD_WALLET,
                Purchase::METHOD_SMS,
                Purchase::METHOD_SERVICE_CODE,
            ])
        ) {
            $purchase->setPayment([
                Purchase::PAYMENT_PAYMENT_ID => $paymentId,
            ]);
            $boughtServiceId = $serviceModule->purchase($purchase);

            return [
                'status' => "purchased",
                'text' => $this->lang->t('purchase_success'),
                'positive' => true,
                'data' => ['bsid' => $boughtServiceId],
            ];
        }

        if ($purchase->getPayment(Purchase::PAYMENT_METHOD) == Purchase::METHOD_TRANSFER) {
            $purchase->setDesc(
                $this->lang->t('payment_for_service', $serviceModule->service->getName())
            );

            return $this->transferPaymentService->payWithTransfer($paymentModule, $purchase);
        }

        throw new UnexpectedValueException();
    }
}
