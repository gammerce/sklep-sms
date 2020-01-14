<?php
namespace App\Http\Services;

use App\Models\Purchase;
use App\Payment\PaymentService;
use App\Repositories\PriceRepository;
use App\ServiceModules\ServiceModule;
use App\Services\SmsPriceService;
use App\System\Auth;
use App\System\Heart;

class PurchaseService
{
    /** @var PaymentService */
    private $paymentService;

    /** @var Heart */
    private $heart;

    /** * @var Auth */
    private $auth;

    /** @var PriceRepository */
    private $priceRepository;

    /** @var SmsPriceService */
    private $smsPriceService;

    public function __construct(
        PaymentService $paymentService,
        Heart $heart,
        Auth $auth,
        PriceRepository $priceRepository,
        SmsPriceService $smsPriceService
    ) {
        $this->paymentService = $paymentService;
        $this->heart = $heart;
        $this->auth = $auth;
        $this->priceRepository = $priceRepository;
        $this->smsPriceService = $smsPriceService;
    }

    public function purchase(ServiceModule $serviceModule, array $body)
    {
        $serverId = array_get($body, 'server_id');
        $type = array_get($body, 'type');
        $authData = array_get($body, 'auth_data');
        $password = array_get($body, 'password');
        $ip = array_get($body, 'ip');
        $method = array_get($body, 'method');
        $smsCode = array_get($body, 'sms_code');
        $paymentPlatformId = array_get($body, 'payment_platform_id');
        $priceId = array_get($body, 'price_id');

        $user = $this->auth->user();
        $user->setLastIp($ip);

        $price = $this->priceRepository->get($priceId);

        $purchase = new Purchase($user);
        $purchase->setService($serviceModule->service->getId());

        $purchase->setOrder([
            'server' => $serverId,
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

        $purchase->setPrice($price);

        // Remove price if it is not supported by this sms_platform
        if ($purchase->getPayment('sms_platform')) {
            $paymentModule = $this->heart->getPaymentModuleByPlatformIdOrFail(
                $purchase->getPayment('sms_platform')
            );

            if (!$this->smsPriceService->isPriceAvailable($price->getSmsPrice(), $paymentModule)) {
                $purchase->setPrice(null);
            }
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
