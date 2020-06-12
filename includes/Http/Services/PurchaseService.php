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
    /** @var PaymentService */
    private $paymentService;

    /** @var Auth */
    private $auth;

    /** @var PriceRepository */
    private $priceRepository;

    /** @var Settings */
    private $settings;

    /** @var Translator */
    private $lang;

    public function __construct(
        PaymentService $paymentService,
        Auth $auth,
        PriceRepository $priceRepository,
        Settings $settings,
        TranslationManager $translationManager
    ) {
        $this->paymentService = $paymentService;
        $this->auth = $auth;
        $this->priceRepository = $priceRepository;
        $this->settings = $settings;
        $this->lang = $translationManager->user();
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

        $paymentPlatformId = $server->getSmsPlatformId() ?: $this->settings->getSmsPlatformId();
        $paymentOption = $this->getPaymentOption($paymentMethod, $paymentPlatformId);
        $price = $this->priceRepository->get($priceId);

        $user = $this->auth->user();
        $user->setLastIp($ip);

        $purchase = (new Purchase($user))
            ->setServiceId($serviceModule->service->getId())
            ->setDescription($this->lang->t("payment_for_service", $serviceModule->service->getNameI18n()))
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
    ) {
        if (PaymentMethod::SMS()->equals($paymentMethod)) {
            return new PaymentOption(PaymentMethod::SMS(), $paymentPlatformId);
        }

        if (PaymentMethod::WALLET()->equals($paymentMethod)) {
            return new PaymentOption(PaymentMethod::WALLET());
        }

        throw new UnexpectedValueException("Unexpected payment method");
    }
}
