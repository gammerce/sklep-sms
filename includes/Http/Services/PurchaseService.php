<?php
namespace App\Http\Services;

use App\Models\Purchase;
use App\Payment\PaymentService;
use App\Repositories\PriceRepository;
use App\ServiceModules\ServiceModule;
use App\System\Auth;

class PurchaseService
{
    /** @var PaymentService */
    private $paymentService;

    /** @var Auth */
    private $auth;

    /** @var PriceRepository */
    private $priceRepository;

    public function __construct(
        PaymentService $paymentService,
        Auth $auth,
        PriceRepository $priceRepository
    ) {
        $this->paymentService = $paymentService;
        $this->auth = $auth;
        $this->priceRepository = $priceRepository;
    }

    public function purchase(ServiceModule $serviceModule, array $body)
    {
        $serverId = as_int(array_get($body, 'server_id'));
        $type = as_int(array_get($body, 'type'));
        $authData = array_get($body, 'auth_data');
        $password = array_get($body, 'password');
        $ip = array_get($body, 'ip');
        $method = array_get($body, 'method');
        $smsCode = array_get($body, 'sms_code');
        $paymentPlatformId = as_int(array_get($body, 'payment_platform_id'));
        $priceId = as_int(array_get($body, 'price_id'));
        $email = array_get($body, 'email');

        $price = $this->priceRepository->get($priceId);

        $user = $this->auth->user();
        $user->setLastIp($ip);

        $purchase = new Purchase($user);
        $purchase->setService($serviceModule->service->getId());

        $purchase->setEmail($email);
        $purchase->setOrder([
            Purchase::ORDER_SERVER => $serverId,
            'type' => $type,
            'auth_data' => $authData,
            'password' => $password,
            'passwordr' => $password,
        ]);

        $purchase->setPayment([
            Purchase::PAYMENT_METHOD => $method,
            Purchase::PAYMENT_SMS_CODE => $smsCode,
            Purchase::PAYMENT_SMS_PLATFORM => $paymentPlatformId,
        ]);

        if ($price) {
            $purchase->setPrice($price);
        }

        $returnValidation = $serviceModule->purchaseDataValidate($purchase);

        if ($returnValidation['status'] !== "ok") {
            $extraData = [];

            if (isset($returnValidation["data"]["warnings"])) {
                $extraData['warnings'] = collect($returnValidation["data"]["warnings"])
                    ->map(function ($warning, $key) {
                        $text = implode("<br />", $warning);
                        return "<strong>{$key}</strong><br />{$text}<br />";
                    })
                    ->join();
            }

            return [
                "status" => $returnValidation['status'],
                "text" => $returnValidation['text'],
                "positive" => $returnValidation['positive'],
                "extraData" => $extraData,
            ];
        }

        $returnPayment = $this->paymentService->makePayment($purchase);

        $extraData = [];

        if (isset($returnPayment['data']['bsid'])) {
            $extraData['bsid'] = $returnPayment['data']['bsid'];
        }

        if (isset($returnPayment['data']['warnings'])) {
            $extraData['warnings'] = collect($returnPayment['data']['warnings'])
                ->map(function ($warning, $key) {
                    return "<strong>{$key}</strong><br />{$warning}<br />";
                })
                ->join();
        }

        return [
            "status" => $returnPayment['status'],
            "text" => $returnPayment['text'],
            "positive" => $returnPayment['positive'],
            "extraData" => $extraData,
        ];
    }
}
