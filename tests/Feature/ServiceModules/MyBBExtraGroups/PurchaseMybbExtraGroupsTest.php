<?php
namespace Tests\Feature\ServiceModules\MyBBExtraGroups;

use App\Models\Purchase;
use App\Models\User;
use App\Payment\General\PaymentMethod;
use App\Payment\General\PaymentResultType;
use App\Payment\General\PaymentService;
use App\Repositories\BoughtServiceRepository;
use App\ServiceModules\MybbExtraGroups\MybbExtraGroupsServiceModule;
use App\Verification\PaymentModules\Cssetti;
use Tests\Psr4\Concerns\CssettiConcern;
use Tests\Psr4\Concerns\MybbRepositoryConcern;
use Tests\Psr4\Concerns\PaymentModuleFactoryConcern;
use Tests\Psr4\TestCases\TestCase;

class PurchaseMybbExtraGroupsTest extends TestCase
{
    use CssettiConcern;
    use PaymentModuleFactoryConcern;
    use MybbRepositoryConcern;

    /** @var PaymentService */
    private $paymentService;

    /** @var BoughtServiceRepository */
    private $boughtServiceRepository;

    protected function setUp()
    {
        parent::setUp();
        $this->mockCSSSettiGetData();
        $this->mockPaymentModuleFactory();
        $this->makeVerifySmsSuccessful(Cssetti::class);
        $this->mockMybbRepository();
        $this->paymentService = $this->app->make(PaymentService::class);
        $this->boughtServiceRepository = $this->app->make(BoughtServiceRepository::class);
    }

    /** @test */
    public function purchase_mybb_extra_groups()
    {
        // given
        $this->mybbRepositoryMock
            ->shouldReceive("updateGroups")
            ->withArgs([1, [1, 5, 2], 1])
            ->andReturnNull();

        $service = $this->factory->service([
            "module" => MybbExtraGroupsServiceModule::MODULE_ID,
            "data" => [
                "db_host" => "host",
                "db_name" => "name",
                "db_password" => "password",
                "db_user" => "user",
                "mybb_groups" => "1,5",
            ],
        ]);

        $price = $this->factory->price([
            "sms_price" => 200,
            "service_id" => $service->getId(),
            "quantity" => 10,
        ]);

        $paymentPlatform = $this->factory->paymentPlatform([
            "module" => Cssetti::MODULE_ID,
        ]);

        $purchase = (new Purchase(new User()))
            ->setServiceId($service->getId())
            ->setUsingPrice($price)
            ->setOrder([
                "username" => "seek",
            ])
            ->setPayment([
                Purchase::PAYMENT_PLATFORM_SMS => $paymentPlatform->getId(),
                Purchase::PAYMENT_SMS_CODE => "abcd1234",
                Purchase::PAYMENT_METHOD => PaymentMethod::SMS(),
            ]);

        // when
        $paymentResult = $this->paymentService->makePayment($purchase);

        // then
        $this->assertSameEnum(PaymentResultType::PURCHASED(), $paymentResult->getType());
        $boughtService = $this->boughtServiceRepository->get($paymentResult->getData());
        $this->assertNotNull($boughtService);
        $this->assertSame(0, $boughtService->getServerId());
        $this->assertSame($service->getId(), $boughtService->getServiceId());
        $this->assertSame(0, $boughtService->getUserId());
        $this->assertSameEnum(PaymentMethod::SMS(), $boughtService->getMethod());
        $this->assertEquals(10, $boughtService->getAmount());
        $this->assertSame("seek (1)", $boughtService->getAuthData());
        $this->assertInternalType("string", $boughtService->getPaymentId());
        $this->assertSame("", $boughtService->getEmail());
        $this->assertNull($boughtService->getPromoCode());
        $this->assertSame(["uid" => 1, "groups" => "1,5"], $boughtService->getExtraData());
    }
}
