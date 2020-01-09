<?php
namespace App\Http\Services;

use App\Models\Purchase;
use App\Payment\PaymentService;
use App\Repositories\PaymentPlatformRepository;
use App\Repositories\UserRepository;
use App\ServiceModules\ServiceModule;
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

    /** @var PaymentPlatformRepository */
    private $paymentPlatformRepository;

    public function __construct(
        TranslationManager $translationManager,
        PaymentService $paymentService,
        Heart $heart,
        Auth $auth,
        UserRepository $userRepository,
        PaymentPlatformRepository $paymentPlatformRepository
    ) {
        $this->lang = $translationManager->user();
        $this->paymentService = $paymentService;
        $this->userRepository = $userRepository;
        $this->heart = $heart;
        $this->auth = $auth;
        $this->paymentPlatformRepository = $paymentPlatformRepository;
    }

    public function purchase(ServiceModule $serviceModule, array $body)
    {
        $server = array_get($body, 'server');
        $type = array_get($body, 'type');
        $authData = array_get($body, 'auth_data');
        $password = array_get($body, 'password');
        $ip = array_get($body, 'ip');
        $method = array_get($body, 'method');
        $smsCode = array_get($body, 'sms_code');
        $paymentPlatformId = array_get($body, 'payment_platform');
        $tariff = array_get($body, 'tariff');

        $user = $this->auth->user();
        $user->setLastIp($ip);

        $purchase = new Purchase($user);
        $purchase->setService($serviceModule->service->getId());

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
            'sms_platform' => $paymentPlatformId,
        ]);

        $purchase->setTariff($this->heart->getTariff($tariff));

        if ($purchase->getPayment('sms_platform')) {
            $paymentModule = $this->heart->getPaymentModuleByPlatformIdOrFail(
                $purchase->getPayment('sms_platform')
            );
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
            'sms_platform' => $paymentPlatformId,
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
