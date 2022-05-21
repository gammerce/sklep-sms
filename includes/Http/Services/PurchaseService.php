<?php
namespace App\Http\Services;

use App\Exceptions\InvalidServiceModuleException;
use App\Exceptions\ValidationException;
use App\Models\Purchase;
use App\Models\Server;
use App\Payment\Exceptions\PaymentProcessingException;
use App\Payment\General\PaymentMethod;
use App\Payment\General\PaymentOption;
use App\Payment\General\PaymentResult;
use App\Payment\General\PaymentService;
use App\Repositories\PriceRepository;
use App\ServiceModules\Interfaces\IServicePurchaseExternal;
use App\ServiceModules\ServiceModule;
use App\System\Auth;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use UnexpectedValueException;

class PurchaseService
{
    private PaymentService $paymentService;
    private Auth $auth;
    private PriceRepository $priceRepository;
    private Settings $settings;

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
     * @param string $platform
     * @return PaymentResult
     * @throws ValidationException
     * @throws PaymentProcessingException
     * @throws InvalidServiceModuleException
     */
    public function purchase(
        IServicePurchaseExternal $serviceModule,
        Server $server,
        array $body,
        $platform
    ): PaymentResult {
        $type = as_int(array_get($body, "type"));
        $authData = trim(array_get($body, "auth_data", ""));
        $password = array_get($body, "password");
        $ip = array_get($body, "ip");
        $smsCode = trim(array_get($body, "sms_code", ""));
        $priceId = as_int(array_get($body, "price_id"));
        $email = trim(array_get($body, "email", ""));
        $paymentMethod = as_payment_method(array_get($body, "method"));

        $paymentPlatformId = $server->getSmsPlatformId() ?: $this->settings->getSmsPlatformId();
        $paymentOption = $this->getPaymentOption($paymentMethod, $paymentPlatformId);
        $price = $this->priceRepository->get($priceId);

        $purchase = (new Purchase($this->auth->user(), $ip, $platform))
            ->setService($serviceModule->service->getId(), $serviceModule->service->getName())
            ->setEmail($email)
            ->setPaymentOption($paymentOption)
            ->setOrder([
                Purchase::ORDER_SERVER => $server->getId(),
                "type" => $type,
                "auth_data" => $authData,
                "password" => $password,
                "passwordr" => $password,
            ])
            ->setPayment([
                Purchase::PAYMENT_SMS_CODE => $smsCode,
            ]);

        $purchase->getPaymentSelect()->setSmsPaymentPlatform($paymentPlatformId);

        if ($price) {
            $purchase->setUsingPrice($price);
        }

        $validator = $serviceModule->purchaseDataValidate($purchase);
        $validator->validateOrFail();

        return $this->paymentService->makePayment($purchase);
    }

    /**
     * @param PaymentMethod|null $paymentMethod
     * @param int|null $paymentPlatformId
     * @return PaymentOption
     * @throws UnexpectedValueException
     */
    private function getPaymentOption(
        PaymentMethod $paymentMethod = null,
        $paymentPlatformId = null
    ): PaymentOption {
        if (PaymentMethod::SMS()->equals($paymentMethod)) {
            return new PaymentOption(PaymentMethod::SMS(), $paymentPlatformId);
        }

        if (PaymentMethod::WALLET()->equals($paymentMethod)) {
            return new PaymentOption(PaymentMethod::WALLET());
        }

        throw new UnexpectedValueException("Unexpected payment method");
    }
}
