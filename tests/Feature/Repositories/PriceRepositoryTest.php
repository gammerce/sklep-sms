<?php
namespace Tests\Feature\Repositories;

use App\Repositories\PriceRepository;
use Tests\Psr4\TestCases\TestCase;

class PriceRepositoryTest extends TestCase
{
    private PriceRepository $priceRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->priceRepository = $this->app->make(PriceRepository::class);
    }

    /** @test */
    public function creates_price()
    {
        // given
        $server = $this->factory->server();

        // when
        $price = $this->priceRepository->create("vip", $server->getId(), 1, 10, 15, 100, null);

        // then
        $this->assertSame("vip", $price->getServiceId());
        $this->assertSame($server->getId(), $price->getServerId());
        $this->assertEqualsMoney(1, $price->getSmsPrice());
        $this->assertTrue($price->hasSmsPrice());
        $this->assertEqualsMoney(10, $price->getTransferPrice());
        $this->assertEqualsMoney(15, $price->getDirectBillingPrice());
        $this->assertTrue($price->hasTransferPrice());
        $this->assertSame(100, $price->getQuantity());
    }

    /** @test */
    public function creates_price_for_all_servers()
    {
        // when
        $price = $this->priceRepository->create("vip", null, 1, 10, 50, 100, null);

        // then
        $this->assertNull($price->getServerId());
    }

    /** @test */
    public function creates_sms_price()
    {
        // when
        $price = $this->priceRepository->create("vip", null, 2, null, null, 100, null);

        // then
        $this->assertNull($price->getServerId());
        $this->assertNull($price->getTransferPrice());
        $this->assertNull($price->getDirectBillingPrice());
        $this->assertFalse($price->hasTransferPrice());
        $this->assertEqualsMoney(2, $price->getSmsPrice());
    }

    /** @test */
    public function creates_transfer_price()
    {
        // when
        $price = $this->priceRepository->create("vip", null, null, 10, null, 100, null);

        // then
        $this->assertNull($price->getServerId());
        $this->assertNull($price->getSmsPrice());
        $this->assertNull($price->getDirectBillingPrice());
        $this->assertFalse($price->hasSmsPrice());
        $this->assertEqualsMoney(10, $price->getTransferPrice());
    }
}
