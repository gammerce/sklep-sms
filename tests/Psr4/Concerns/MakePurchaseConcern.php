<?php
namespace Tests\Psr4\Concerns;

use App\Http\Services\PurchaseService;
use App\Models\PaymentPlatform;
use App\Models\Price;
use App\Models\Purchase;
use App\Models\Server;
use App\Repositories\BoughtServiceRepository;
use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\System\Heart;
use App\Verification\PaymentModules\Cssetti;
use UnexpectedValueException;

trait MakePurchaseConcern
{
    use PaymentModuleFactoryConcern;

    protected function createRandomPurchase(array $attributes = [])
    {
        $this->mockPaymentModuleFactory();
        $this->makeVerifySmsSuccessful(Cssetti::class);

        /** @var Heart $heart */
        $heart = $this->app->make(Heart::class);

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
                'payment_platform_id' => $paymentPlatform->getId(),
                'server_id' => $server->getId(),
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

        $serviceModule = $heart->getServiceModule('vip');
        $result = $purchaseService->purchase($serviceModule, $attributes);

        if ($result['status'] !== 'purchased') {
            throw new UnexpectedValueException();
        }

        return $boughtServiceRepository->get($result["data"]["bsid"]);
    }
}
