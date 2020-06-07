<?php
namespace App\Http\Services;

use App\Exceptions\InvalidServiceModuleException;
use App\Exceptions\ValidationException;
use App\Models\Purchase;
use App\Models\Server;
use App\Payment\Exceptions\PaymentProcessingException;
use App\Payment\General\PaymentResult;
use App\Payment\General\PaymentService;
use App\Repositories\PriceRepository;
use App\ServiceModules\Interfaces\IServicePurchaseExternal;
use App\ServiceModules\ServiceModule;
use App\System\Auth;
use App\System\Settings;

class PurchaseService
{
    /** @var PaymentService */
    private $paymentService;

    /** @var Auth */
    private $auth;

    /** @var PriceRepository */
    private $priceRepository;

    /** @var Settings */
    private $settings;

    public function __construct(
        PaymentService $paymentService,
        Auth $auth,
        PriceRepository $priceRepository,
        Settings $settings
    ) {
        $this->paymentService = $paymentService;
        $this->auth = $auth;
        $this->priceRepository = $priceRepository;
        $this->settings = $settings;
    }

    /**
     * @param IServicePurchaseExternal|ServiceModule $serviceModule
     * @param Server $server
     * @param array $body
     * @return PaymentResult
     * @throws ValidationException
     * @throws PaymentProcessingException
     * @throws InvalidServiceModuleException
     */
    public function purchase(IServicePurchaseExternal $serviceModule, Server $server, array $body)
    {
        $type = as_int(array_get($body, "type"));
        $authData = trim(array_get($body, "auth_data"));
        $password = array_get($body, "password");
        $ip = array_get($body, "ip");
        $smsCode = trim(array_get($body, "sms_code"));
        $priceId = as_int(array_get($body, "price_id"));
        $email = trim(array_get($body, "email"));
        $paymentMethod = as_payment_method(array_get($body, "method"));

        $price = $this->priceRepository->get($priceId);

        $user = $this->auth->user();
        $user->setLastIp($ip);

        $purchase = (new Purchase($user))
            ->setServiceId($serviceModule->service->getId())
            ->setEmail($email)
            ->setOrder([
                Purchase::ORDER_SERVER => $server->getId(),
                "type" => $type,
                "auth_data" => $authData,
                "password" => $password,
                "passwordr" => $password,
            ])
            ->setPayment([
                Purchase::PAYMENT_METHOD => $paymentMethod,
                Purchase::PAYMENT_SMS_CODE => $smsCode,
            ]);

        $purchase
            ->getPaymentSelect()
            ->setSmsPaymentPlatform(
                $server->getSmsPlatformId() ?: $this->settings->getSmsPlatformId()
            );

        if ($price) {
            $purchase->setUsingPrice($price);
        }

        $validator = $serviceModule->purchaseDataValidate($purchase);
        $validator->validateOrFail();

        return $this->paymentService->makePayment($purchase);
    }
}
