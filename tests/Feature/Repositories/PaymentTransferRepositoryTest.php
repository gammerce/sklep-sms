<?php
namespace Tests\Feature\Repositories;

use App\Repositories\PaymentTransferRepository;
use Tests\Psr4\TestCases\TestCase;

class PaymentTransferRepositoryTest extends TestCase
{
    /** @test */
    public function creates_payment()
    {
        // given
        /** @var PaymentTransferRepository $paymentTransferRepository */
        $paymentTransferRepository = $this->app->make(PaymentTransferRepository::class);

        // when
        $paymentTransfer = $paymentTransferRepository->create("test", 1, "a", "b", "c", true);

        // then
        $this->assertSame("test", $paymentTransfer->getId());
        $this->assertSame(1, $paymentTransfer->getIncome());
        $this->assertSame("a", $paymentTransfer->getTransferService());
        $this->assertSame("b", $paymentTransfer->getIp());
        $this->assertSame("c", $paymentTransfer->getPlatform());
        $this->assertSame(true, $paymentTransfer->isFree());
    }

    /** @test */
    public function creates_free_payment()
    {
        // given
        /** @var PaymentTransferRepository $paymentTransferRepository */
        $paymentTransferRepository = $this->app->make(PaymentTransferRepository::class);

        // when
        $paymentTransfer = $paymentTransferRepository->create("test", 1, "a", "b", "c", false);

        // then
        $this->assertSame(false, $paymentTransfer->isFree());
    }
}
