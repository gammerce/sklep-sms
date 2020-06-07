<?php
namespace Tests\Psr4\Concerns;

use App\Managers\ServiceModuleManager;
use App\Models\BoughtService;
use App\Models\PaymentPlatform;
use App\Models\Price;
use App\Models\Purchase;
use App\Models\Server;
use App\Models\Service;
use App\Models\User;
use App\Payment\General\PaymentMethod;
use App\Payment\General\PaymentOption;
use App\Payment\General\PaymentResultType;
use App\Payment\General\PaymentService;
use App\Repositories\BoughtServiceRepository;
use App\Repositories\SettingsRepository;
use App\ServiceModules\ExtraFlags\ExtraFlagsServiceModule;
use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\ServiceModules\MybbExtraGroups\MybbExtraGroupsServiceModule;
use App\System\Settings;
use App\Verification\PaymentModules\Cssetti;
use UnexpectedValueException;

trait MakePurchaseConcern
{
    use PaymentModuleFactoryConcern;
    use MybbRepositoryConcern;
    use CssettiConcern;

    /**
     * @param array $attributes
     * @return BoughtService
     */
    public function createRandomExtraFlagsPurchase(array $attributes = [])
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
        $price = $this->factory->price([
            "service_id" => $serviceId,
            "sms_price" => 100,
        ]);

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
            ->setPaymentOption(new PaymentOption(PaymentMethod::SMS(), $paymentPlatform->getId()))
            ->setPayment([
                Purchase::PAYMENT_SMS_CODE => $attributes["sms_code"],
            ])
            ->setUsingPrice($price);

        $purchase->getPaymentSelect()->setSmsPaymentPlatform($paymentPlatform->getId());

        $serviceModule->purchaseDataValidate($purchase)->validateOrFail();

        $paymentResult = $paymentService->makePayment($purchase);

        if ($paymentResult->getType()->equals(PaymentResultType::PURCHASED())) {
            return $boughtServiceRepository->get($paymentResult->getData());
        }

        throw new UnexpectedValueException();
    }

    /**
     * @param array $attributes
     * @return BoughtService
     */
    public function createRandomMybbPurchase(array $attributes = [])
    {
        /** @var SettingsRepository $settingsRepository */
        $settingsRepository = $this->app->make(SettingsRepository::class);

        /** @var Settings $settings */
        $settings = $this->app->make(Settings::class);

        $this->mockCSSSettiGetData();
        $this->mockPaymentModuleFactory();
        $this->makeVerifySmsSuccessful(Cssetti::class);
        $this->mockMybbRepository();

        $this->mybbRepositoryMock->shouldReceive("existsByUsername")->andReturnTrue();

        $this->mybbRepositoryMock
            ->shouldReceive("updateGroups")
            ->withArgs([1, [1, 2], 1])
            ->andReturnNull();

        /** @var ServiceModuleManager $serviceModuleManager */
        $serviceModuleManager = $this->app->make(ServiceModuleManager::class);

        /** @var PaymentService $paymentService */
        $paymentService = $this->app->make(PaymentService::class);

        /** @var BoughtServiceRepository $boughtServiceRepository */
        $boughtServiceRepository = $this->app->make(BoughtServiceRepository::class);

        /** @var Service $service */
        $service = $this->factory->mybbService();

        /** @var Price $price */
        $price = $this->factory->price([
            "service_id" => $service->getId(),
            "sms_price" => 100,
        ]);

        /** @var PaymentPlatform $paymentPlatform */
        $paymentPlatform = $this->factory->paymentPlatform([
            "module" => Cssetti::MODULE_ID,
        ]);

        $settingsRepository->update([
            "sms_platform" => $paymentPlatform->getId(),
        ]);
        $settings->load();

        $attributes = array_merge(
            [
                "quantity" => $price->getQuantity(),
                "username" => "example",
                "sms_code" => "mycode",
                "email" => "example@abc.pl",
            ],
            $attributes
        );

        /** @var MybbExtraGroupsServiceModule $serviceModule */
        $serviceModule = $serviceModuleManager->get($service->getId());

        $purchase = (new Purchase(new User()))
            ->setServiceId($service->getId())
            ->setPaymentOption(new PaymentOption(PaymentMethod::SMS(), $paymentPlatform->getId()))
            ->setPayment([
                Purchase::PAYMENT_SMS_CODE => $attributes["sms_code"],
            ]);

        $purchase->getPaymentSelect()->setSmsPaymentPlatform($paymentPlatform->getId());

        $serviceModule->purchaseFormValidate($purchase, $attributes);

        $paymentResult = $paymentService->makePayment($purchase);

        if ($paymentResult->getType()->equals(PaymentResultType::PURCHASED())) {
            return $boughtServiceRepository->get($paymentResult->getData());
        }

        throw new UnexpectedValueException();
    }
}
