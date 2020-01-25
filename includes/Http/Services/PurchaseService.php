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

        $price = $this->priceRepository->get($priceId);

        $user = $this->auth->user();
        $user->setLastIp($ip);

        $purchase = new Purchase($user);
        $purchase->setService($serviceModule->service->getId());

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
            $data = array_get($returnValidation, 'data', []);
            $warnings = array_get($data, 'warnings', []);

            $warningsText = collect($warnings)
                ->map(function ($warning, $key) {
                    return "<strong>{$key}</strong><br />" . implode("<br />", $warning) . "<br />";
                })
                ->join();

            $extraData = $warningsText ? "<warnings>$warningsText</warnings>" : '';

            return [
                "status" => $returnValidation['status'],
                "text" => $returnValidation['text'],
                "positive" => $returnValidation['positive'],
                "extraData" => $extraData,
            ];
        }

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
