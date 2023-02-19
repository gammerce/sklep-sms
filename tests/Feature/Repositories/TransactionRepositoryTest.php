<?php

namespace Tests\Feature\Repositories;

use App\Repositories\TransactionRepository;
use Tests\Psr4\Concerns\MakePurchaseConcern;
use Tests\Psr4\TestCases\TestCase;

class TransactionRepositoryTest extends TestCase
{
    use MakePurchaseConcern;

    private TransactionRepository $transactionRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transactionRepository = $this->app->make(TransactionRepository::class);
    }

    /** @test */
    public function get_by_payment()
    {
        // given
        $boughtService = $this->createRandomExtraFlagsPurchase();

        // when
        $transaction = $this->transactionRepository->getByPaymentId($boughtService->getPaymentId());

        // then
        $this->assertSame($boughtService->getId(), $transaction->getId());
    }
}
