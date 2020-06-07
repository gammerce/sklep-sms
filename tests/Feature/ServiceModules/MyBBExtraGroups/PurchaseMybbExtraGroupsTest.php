<?php
namespace Tests\Feature\ServiceModules\MyBBExtraGroups;

use App\Models\Purchase;
use App\Models\User;
use App\Payment\General\PaymentMethod;
use App\Payment\General\PaymentResultType;
use App\Payment\General\PaymentService;
use App\Repositories\BoughtServiceRepository;
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
        $this->mockMybbRepository();
        $this->paymentService = $this->app->make(PaymentService::class);
        $this->boughtServiceRepository = $this->app->make(BoughtServiceRepository::class);
    }

    /** @test */
    public function purchase_mybb_extra_groups()
    {
        // given
        $this->mockCSSSettiGetData();
        $this->mockPaymentModuleFactory();
        $this->makeVerifySmsSuccessful(Cssetti::class);

        $this->mybbRepositoryMock
            ->shouldReceive("updateGroups")
            ->withArgs([1, [1, 2, 5], 1])
            ->andReturnNull();

        $service = $this->factory->mybbService([
            "data" => [
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
                Purchase::PAYMENT_SMS_CODE => "abcd1234",
                Purchase::PAYMENT_METHOD => PaymentMethod::SMS(),
            ]);

        $purchase->getPaymentPlatformSelect()->setSmsPaymentPlatform($paymentPlatform->getId());

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

        $this->assertDatabaseHas("ss_user_service", [
            "service_id" => $service->getId(),
            "user_id" => 0,
        ]);
        $this->assertDatabaseHas("ss_user_service_mybb_extra_groups", [
            "service_id" => $service->getId(),
            "mybb_uid" => 1,
        ]);
        $this->assertDatabaseHas("ss_mybb_user_group", [
            "uid" => 1,
            "gid" => 1,
            "was_before" => true,
        ]);
        $this->assertDatabaseHas("ss_mybb_user_group", [
            "uid" => 1,
            "gid" => 5,
            "was_before" => false,
        ]);
    }

    /** @test */
    public function purchase_service_forever()
    {
        $this->mybbRepositoryMock
            ->shouldReceive("updateGroups")
            ->withArgs([1, [1, 2, 8], 1])
            ->andReturnNull();

        $user = $this->factory->user([
            "wallet" => 500,
        ]);

        $service = $this->factory->mybbService([
            "data" => [
                "mybb_groups" => "8",
            ],
        ]);

        $price = $this->factory->price([
            "transfer_price" => 200,
            "service_id" => $service->getId(),
            "quantity" => null,
        ]);

        $purchase = (new Purchase($user))
            ->setServiceId($service->getId())
            ->setUsingPrice($price)
            ->setOrder([
                "username" => "seek",
            ])
            ->setPayment([
                Purchase::PAYMENT_METHOD => PaymentMethod::WALLET(),
            ]);

        // when
        $paymentResult = $this->paymentService->makePayment($purchase);

        // then
        $this->assertSameEnum(PaymentResultType::PURCHASED(), $paymentResult->getType());
        $boughtService = $this->boughtServiceRepository->get($paymentResult->getData());
        $this->assertNotNull($boughtService);
        $this->assertSameEnum(PaymentMethod::WALLET(), $boughtService->getMethod());

        $this->assertDatabaseHas("ss_user_service", [
            "service_id" => $service->getId(),
            "expire" => -1,
            "user_id" => $user->getId(),
        ]);
        $this->assertDatabaseHas("ss_user_service_mybb_extra_groups", [
            "service_id" => $service->getId(),
            "mybb_uid" => 1,
        ]);

        $this->assertDatabaseHas("ss_mybb_user_group", [
            "uid" => 1,
            "gid" => 8,
            "expire" => null,
            "was_before" => false,
        ]);

        $this->assertDatabaseHas("ss_users", [
            "uid" => $user->getId(),
            "wallet" => 300,
        ]);
    }
}
