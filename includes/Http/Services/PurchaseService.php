<?php
namespace App\Http\Services;

use App\Models\Purchase;
use App\Payment;
use App\Payment\PaymentService;
use App\Services\Service;
use App\System\Heart;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class PurchaseService
{
    /** @var Heart */
    private $heart;

    /** @var Translator */
    private $lang;

    /** @var PaymentService */
    private $paymentService;

    public function __construct(
        Heart $heart,
        TranslationManager $translationManager,
        PaymentService $paymentService
    ) {
        $this->heart = $heart;
        $this->lang = $translationManager->user();
        $this->paymentService = $paymentService;
    }

    public function payWithSms(Service $serviceModule, array $body)
    {
        $uid = array_get($body, 'uid');
        $server = array_get($body, 'server');
        $type = array_get($body, 'type');
        $authData = array_get($body, 'auth_data');
        $password = array_get($body, 'password');
        $ip = array_get($body, 'ip');
        $method = array_get($body, 'method');
        $platform = array_get($body, 'platform');
        $smsCode = array_get($body, 'sms_code');
        $transactionService = array_get($body, 'transaction_service');
        $tariff = array_get($body, 'tariff');

        // Sprawdzamy dane zakupu
        $purchaseData = new Purchase();
        $purchaseData->setService($serviceModule->service['id']);
        $purchaseData->user = $this->heart->getUser($uid);
        $purchaseData->user->setPlatform($platform);
        $purchaseData->user->setLastIp($ip);

        $purchaseData->setOrder([
            'server' => $server,
            'type' => $type,
            'auth_data' => $authData,
            'password' => $password,
            'passwordr' => $password,
        ]);

        $purchaseData->setPayment([
            'method' => $method,
            'sms_code' => $smsCode,
            'sms_service' => $transactionService,
        ]);

        // Ustawiamy taryfę z numerem
        $payment = new Payment($purchaseData->getPayment('sms_service'));
        $purchaseData->setTariff($payment->getPaymentModule()->getTariffById($tariff));

        $returnValidation = $serviceModule->purchaseDataValidate($purchaseData);

        // Są jakieś błędy przy sprawdzaniu danych
        if ($returnValidation['status'] != "ok") {
            $extraData = '';
            if (!empty($returnValidation["data"]["warnings"])) {
                $warnings = '';
                foreach ($returnValidation["data"]["warnings"] as $what => $warning) {
                    $warnings .=
                        "<strong>{$what}</strong><br />" . implode("<br />", $warning) . "<br />";
                }

                if (strlen($warnings)) {
                    $extraData .= "<warnings>{$warnings}</warnings>";
                }
            }

            return [
                "status" => $returnValidation['status'],
                "text" => $returnValidation['text'],
                "positive" => $returnValidation['positive'],
                "extraData" => $extraData,
            ];
        }

        /** @var Purchase $purchaseData */
        $purchaseData = $returnValidation['purchase_data'];
        $purchaseData->setPayment([
            'method' => $method,
            'sms_code' => $smsCode,
            'sms_service' => $transactionService,
        ]);
        $returnPayment = $this->paymentService->makePayment($purchaseData);

        $extraData = "";

        if (isset($returnPayment['data']['bsid'])) {
            $extraData .= "<bsid>{$returnPayment['data']['bsid']}</bsid>";
        }

        if (isset($returnPayment["data"]["warnings"])) {
            $warnings = "";
            foreach ($returnPayment["data"]["warnings"] as $what => $text) {
                $warnings .= "<strong>{$what}</strong><br />{$text}<br />";
            }

            if (strlen($warnings)) {
                $extraData .= "<warnings>{$warnings}</warnings>";
            }
        }

        return [
            "status" => $returnPayment['status'],
            "text" => $returnPayment['text'],
            "positive" => $returnPayment['positive'],
            "extraData" => $extraData,
        ];
    }

    public function payWithWallet(Service $serviceModule, array $body)
    {
        // TODO Implement it
        return [
            "status" => "",
            "text" => "",
            "positive" => "",
            "extraData" => "",
        ];
    }
}
