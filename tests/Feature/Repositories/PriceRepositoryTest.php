<?php
namespace Tests\Feature\Repositories;

use App\Repositories\PriceRepository;
use Tests\Psr4\TestCases\TestCase;

class PriceRepositoryTest extends TestCase
{
    /** @test */
    public function creates_price()
    {
        // given
        /** @var PriceRepository $priceRepository */
        $priceRepository = $this->app->make(PriceRepository::class);

        $server = $this->factory->server();

        // when
        $price = $priceRepository->create("vip", $server->getId(), 1, 10, 100);

        // then
        $this->assertSame("vip", $price->getServiceId());
        $this->assertSame($server->getId(), $price->getServerId());
        $this->assertSame(1, $price->getSmsPrice());
        $this->assertTrue($price->hasSmsPrice());
        $this->assertSame(10, $price->getTransferPrice());
        $this->assertTrue($price->hasTransferPrice());
        $this->assertSame(100, $price->getQuantity());
    }

    /** @test */
    public function creates_price_for_all_servers()
    {
        // given
        /** @var PriceRepository $priceRepository */
        $priceRepository = $this->app->make(PriceRepository::class);

        // when
        $price = $priceRepository->create("vip", null, 1, 10, 100);

        // then
        $this->assertNull($price->getServerId());
    }

    /** @test */
    public function creates_sms_price()
    {
        // given
        /** @var PriceRepository $priceRepository */
        $priceRepository = $this->app->make(PriceRepository::class);

        // when
        $price = $priceRepository->create("vip", null, 2, null, 100);

        // then
        $this->assertNull($price->getServerId());
        $this->assertNull($price->getTransferPrice());
        $this->assertFalse($price->hasTransferPrice());
        $this->assertSame(2, $price->getSmsPrice());
    }

    /** @test */
    public function creates_transfer_price()
    {
        // given
        /** @var PriceRepository $priceRepository */
        $priceRepository = $this->app->make(PriceRepository::class);

        // when
        $price = $priceRepository->create("vip", null, null, 10, 100);

        // then
        $this->assertNull($price->getServerId());
        $this->assertNull($price->getSmsPrice());
        $this->assertFalse($price->hasSmsPrice());
        $this->assertSame(10, $price->getTransferPrice());
    }
}
