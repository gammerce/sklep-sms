<?php
namespace Tests\Feature\Repositories;

use App\Repositories\PaymentTransferRepository;
use Tests\Psr4\TestCases\TestCase;

class PaymentTransferRepositoryTest extends TestCase
{
    /** @var PaymentTransferRepository */
    private $paymentTransferRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentTransferRepository = $this->app->make(PaymentTransferRepository::class);
    }

    /** @test */
    public function creates_payment()
    {
        // when
        $paymentTransfer = $this->paymentTransferRepository->create(
            "test",
            1,
            2,
            "a",
            "b",
            "c",
            false
        );

        // then
        $this->assertSame("test", $paymentTransfer->getId());
        $this->assertEqualsMoney(1, $paymentTransfer->getIncome());
        $this->assertEqualsMoney(2, $paymentTransfer->getCost());
        $this->assertSame("a", $paymentTransfer->getTransferService());
        $this->assertSame("b", $paymentTransfer->getIp());
        $this->assertSame("c", $paymentTransfer->getPlatform());
        $this->assertFalse($paymentTransfer->isFree());
    }

    /** @test */
    public function creates_free_payment()
    {
        // when
        $paymentTransfer = $this->paymentTransferRepository->create(
            "test",
            1,
            1,
            "a",
            "b",
            "c",
            true
        );

        // then
        $this->assertTrue($paymentTransfer->isFree());
    }
}
