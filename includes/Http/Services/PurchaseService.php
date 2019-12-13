<?php
namespace App\Http\Services;

use App\Models\Purchase;
use App\Payment\PaymentService;
use App\Repositories\UserRepository;
use App\Services\Service;
use App\System\Auth;
use App\System\Heart;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class PurchaseService
{
    /** @var Translator */
    private $lang;

    /** @var PaymentService */
    private $paymentService;

    /** @var UserRepository */
    private $userRepository;

    /** @var Heart */
    private $heart;

    /** * @var Auth */
    private $auth;

    public function __construct(
        TranslationManager $translationManager,
        PaymentService $paymentService,
        Heart $heart,
        Auth $auth,
        UserRepository $userRepository
    ) {
        $this->lang = $translationManager->user();
        $this->paymentService = $paymentService;
        $this->userRepository = $userRepository;
        $this->heart = $heart;
        $this->auth = $auth;
    }

    public function purchase(Service $serviceModule, array $body)
    {
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

        $user = $this->auth->user();
        $user->setPlatform($platform);
        $user->setLastIp($ip);

        $purchase = new Purchase($user);
        $purchase->setService($serviceModule->service['id']);

        $purchase->setOrder([
            'server' => $server,
            'type' => $type,
            'auth_data' => $authData,
            'password' => $password,
            'passwordr' => $password,
        ]);

        $purchase->setPayment([
            'method' => $method,
            'sms_code' => $smsCode,
            'sms_service' => $transactionService,
        ]);

        $purchase->setTariff($this->heart->getTariff($tariff));

        if ($purchase->getPayment('sms_service')) {
            $paymentModule = $this->heart->getPaymentModuleOrFail($purchase->getPayment('sms_service'));
            $purchase->setTariff($paymentModule->getTariffById($tariff));
        }

        $returnValidation = $serviceModule->purchaseDataValidate($purchase);

        if ($returnValidation['status'] !== "ok") {
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

        /** @var Purchase $purchase */
        $purchase = $returnValidation['purchase_data'];
        $purchase->setPayment([
            'method' => $method,
            'sms_code' => $smsCode,
            'sms_service' => $transactionService,
        ]);

        $returnPayment = $this->paymentService->makePayment($purchase);

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
}
