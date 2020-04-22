<?php
namespace App\Http\Services;

use App\Exceptions\ValidationException;
use App\Models\Purchase;
use App\Models\Server;
use App\Payment\General\PaymentService;
use App\Repositories\PriceRepository;
use App\ServiceModules\Interfaces\IServicePurchaseExternal;
use App\ServiceModules\ServiceModule;
use App\Support\Result;
use App\System\Auth;
use App\System\Settings;
use UnexpectedValueException;

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
     * @param ServiceModule $serviceModule
     * @param Server $server
     * @param array $body
     * @return Result
     * @throws ValidationException
     */
    public function purchase(ServiceModule $serviceModule, Server $server, array $body)
    {
        if (!($serviceModule instanceof IServicePurchaseExternal)) {
            throw new UnexpectedValueException();
        }

        $type = as_int(array_get($body, 'type'));
        $authData = trim(array_get($body, 'auth_data'));
        $password = array_get($body, 'password');
        $ip = array_get($body, 'ip');
        $method = array_get($body, 'method');
        $smsCode = trim(array_get($body, 'sms_code'));
        $priceId = as_int(array_get($body, 'price_id'));
        $email = trim(array_get($body, 'email'));

        $price = $this->priceRepository->get($priceId);

        $user = $this->auth->user();
        $user->setLastIp($ip);

        $purchase = new Purchase($user);
        $purchase->setServiceId($serviceModule->service->getId());
        $purchase->setEmail($email);
        $purchase->setOrder([
            Purchase::ORDER_SERVER => $server->getId(),
            'type' => $type,
            'auth_data' => $authData,
            'password' => $password,
            'passwordr' => $password,
        ]);

        $purchase->setPayment([
            Purchase::PAYMENT_METHOD => $method,
            Purchase::PAYMENT_SMS_CODE => $smsCode,
            Purchase::PAYMENT_PLATFORM_SMS =>
                $server->getSmsPlatformId() ?: $this->settings->getSmsPlatformId(),
        ]);

        if ($price) {
            $purchase->setUsingPrice($price);
        }

        $validator = $serviceModule->purchaseDataValidate($purchase);
        $validator->validateOrFail();

        return $this->paymentService->makePayment($purchase);
    }
}
