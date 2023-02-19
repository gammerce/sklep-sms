<?php

namespace Tests\Feature\Repositories;

use App\Models\BoughtService;
use App\Repositories\BoughtServiceRepository;
use Tests\Psr4\TestCases\TestCase;

class BoughtServiceRepositoryTest extends TestCase
{
    private BoughtServiceRepository $boughtServiceRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->boughtServiceRepository = $this->app->make(BoughtServiceRepository::class);
    }

    /** @test */
    public function get_bought_service_by_payment_id()
    {
        // given
        $expectedBoughtService = $this->createBoughtService();

        // when
        $boughtService = $this->boughtServiceRepository->getByPaymentId("TX_FOO");

        // then
        $this->assertSame($expectedBoughtService->getId(), $boughtService->getId());
    }

    /** @test */
    public function update_invoice_id()
    {
        // given
        $boughtService = $this->createBoughtService();

        // when
        $result = $this->boughtServiceRepository->update($boughtService->getId(), "1234");

        // then
        $this->assertTrue($result);
        $freshBoughtService = $this->boughtServiceRepository->get($boughtService->getId());
        $this->assertSame("1234", $freshBoughtService->getInvoiceId());
    }

    private function createBoughtService(): BoughtService
    {
        return $this->boughtServiceRepository->create(
            1,
            "transfer",
            "TX_FOO",
            null,
            "vip",
            null,
            30,
            "test",
            "test@example.com",
            null,
            []
        );
    }
}
