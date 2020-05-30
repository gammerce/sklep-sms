<?php
namespace Tests\Psr4\Concerns;

use App\Managers\ServiceModuleManager;
use App\Models\BoughtService;
use App\Models\PaymentPlatform;
use App\Models\Price;
use App\Models\Purchase;
use App\Models\Server;
use App\Models\User;
use App\Payment\General\PaymentMethod;
use App\Payment\General\PaymentResultType;
use App\Payment\General\PaymentService;
use App\Repositories\BoughtServiceRepository;
use App\ServiceModules\ExtraFlags\ExtraFlagsServiceModule;
use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\Verification\PaymentModules\Cssetti;
use UnexpectedValueException;

trait MakePurchaseConcern
{
    use PaymentModuleFactoryConcern;
    use CssettiConcern;

    /**
     * @param array $attributes
     * @return BoughtService
     */
    protected function createRandomExtraFlagsPurchase(array $attributes = [])
    {
        $this->mockCSSSettiGetData();
        $this->mockPaymentModuleFactory();
        $this->makeVerifySmsSuccessful(Cssetti::class);

        /** @var ServiceModuleManager $serviceModuleManager */
        $serviceModuleManager = $this->app->make(ServiceModuleManager::class);

        /** @var PaymentService $paymentService */
        $paymentService = $this->app->make(PaymentService::class);

        /** @var BoughtServiceRepository $boughtServiceRepository */
        $boughtServiceRepository = $this->app->make(BoughtServiceRepository::class);

        /** @var PaymentPlatform $paymentPlatform */
        $paymentPlatform = $this->factory->paymentPlatform([
            "module" => Cssetti::MODULE_ID,
        ]);

        $serviceId = "vip";

        /** @var Server $server */
        $server = $this->factory->server([
            "sms_platform_id" => $paymentPlatform->getId(),
        ]);

        /** @var Price $price */
        $price = $this->factory->price();

        $this->factory->serverService([
            "server_id" => $server->getId(),
            "service_id" => $serviceId,
        ]);

        $attributes = array_merge(
            [
                "price_id" => $price->getId(),
                "type" => ExtraFlagType::TYPE_NICK,
                "auth_data" => "example",
                "password" => "anc123",
                "sms_code" => "mycode",
                "email" => "example@abc.pl",
            ],
            $attributes
        );

        /** @var ExtraFlagsServiceModule $serviceModule */
        $serviceModule = $serviceModuleManager->get($serviceId);

        $purchase = (new Purchase(new User()))
            ->setServiceId($serviceId)
            ->setEmail($attributes["email"])
            ->setOrder([
                Purchase::ORDER_SERVER => $server->getId(),
                "type" => $attributes["type"],
                "auth_data" => $attributes["auth_data"],
                "password" => $attributes["password"],
                "passwordr" => $attributes["password"],
            ])
            ->setPayment([
                Purchase::PAYMENT_METHOD => PaymentMethod::SMS(),
                Purchase::PAYMENT_SMS_CODE => $attributes["sms_code"],
                Purchase::PAYMENT_PLATFORM_SMS => $paymentPlatform->getId(),
            ])
            ->setUsingPrice($price);

        $serviceModule->purchaseDataValidate($purchase)->validateOrFail();

        $paymentResult = $paymentService->makePayment($purchase);

        if ($paymentResult->getType()->equals(PaymentResultType::PURCHASED())) {
            return $boughtServiceRepository->get($paymentResult->getData());
        }

        throw new UnexpectedValueException();
    }
}
