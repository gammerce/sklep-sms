<?php
namespace Tests\Feature\Repositories;

use App\Repositories\PaymentDirectBillingRepository;
use Tests\Psr4\TestCases\TestCase;

class PaymentDirectBillingRepositoryTest extends TestCase
{
    /** @var PaymentDirectBillingRepository */
    private $paymentDirectBillingRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentDirectBillingRepository = $this->app->make(
            PaymentDirectBillingRepository::class
        );
    }

    /** @test */
    public function creates_payment()
    {
        // when
        $paymentTransfer = $this->paymentDirectBillingRepository->create(
            "test",
            1,
            2,
            "192.0.2.1",
            "platform",
            false
        );

        // then
        $this->assertSame("test", $paymentTransfer->getExternalId());
        $this->assertEqualsMoney(1, $paymentTransfer->getIncome());
        $this->assertEqualsMoney(2, $paymentTransfer->getCost());
        $this->assertSame("192.0.2.1", $paymentTransfer->getIp());
        $this->assertSame("platform", $paymentTransfer->getPlatform());
        $this->assertFalse($paymentTransfer->isFree());
    }

    /** @test */
    public function creates_free_payment()
    {
        // when
        $paymentTransfer = $this->paymentDirectBillingRepository->create(
            "test",
            1,
            2,
            "192.0.2.1",
            "platform",
            true
        );

        // then
        $this->assertTrue($paymentTransfer->isFree());
    }
}
