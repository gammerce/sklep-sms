<?php
namespace Tests\Psr4\Concerns;

use App\Http\Services\PurchaseService;
use App\Models\BoughtService;
use App\Models\PaymentPlatform;
use App\Models\Price;
use App\Models\Purchase;
use App\Models\Server;
use App\Repositories\BoughtServiceRepository;
use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\Verification\PaymentModules\Cssetti;
use App\Managers\ServiceModuleManager;
use UnexpectedValueException;

trait MakePurchaseConcern
{
    use PaymentModuleFactoryConcern;

    /**
     * @param array $attributes
     * @return BoughtService
     */
    protected function createRandomPurchase(array $attributes = [])
    {
        $this->mockPaymentModuleFactory();
        $this->makeVerifySmsSuccessful(Cssetti::class);

        /** @var ServiceModuleManager $serviceModuleManager */
        $serviceModuleManager = $this->app->make(ServiceModuleManager::class);

        /** @var PurchaseService $purchaseService */
        $purchaseService = $this->app->make(PurchaseService::class);

        /** @var BoughtServiceRepository $boughtServiceRepository */
        $boughtServiceRepository = $this->app->make(BoughtServiceRepository::class);

        /** @var PaymentPlatform $paymentPlatform */
        $paymentPlatform = $this->factory->paymentPlatform([
            'module' => Cssetti::MODULE_ID,
        ]);

        /** @var Server $server */
        $server = $this->factory->server([
            'sms_platform_id' => $paymentPlatform->getId(),
        ]);

        /** @var Price $price */
        $price = $this->factory->price();

        $this->factory->serverService([
            'server_id' => $server->getId(),
            'service_id' => 'vip',
        ]);

        $attributes = array_merge(
            [
                'price_id' => $price->getId(),
                'type' => ExtraFlagType::TYPE_NICK,
                'auth_data' => "example",
                'password' => "anc123",
                'sms_code' => "mycode",
                'method' => Purchase::METHOD_SMS,
                'ip' => "192.0.2.1",
                'email' => 'example@abc.pl',
            ],
            $attributes
        );

        $serviceModule = $serviceModuleManager->get('vip');
        $result = $purchaseService->purchase($serviceModule, $server, $attributes);

        if ($result->getStatus() !== 'purchased') {
            throw new UnexpectedValueException();
        }

        return $boughtServiceRepository->get($result->getDatum("bsid"));
    }
}
