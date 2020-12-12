<?php
namespace Tests\Feature\Repositories;

use App\PromoCode\QuantityType;
use App\Repositories\PromoCodeRepository;
use DateTime;
use Tests\Psr4\TestCases\TestCase;

class PromoCodeRepositoryTest extends TestCase
{
    /** @var PromoCodeRepository */
    private $promoCodeRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->promoCodeRepository = $this->app->make(PromoCodeRepository::class);
    }

    /** @test */
    public function createsPromoCode()
    {
        // given
        $service = $this->factory->extraFlagService();
        $server = $this->factory->server();
        $user = $this->factory->user();

        // when
        $promoCode = $this->promoCodeRepository->create(
            "EXAMPLE",
            QuantityType::PERCENTAGE(),
            10,
            15,
            new DateTime(),
            $service->getId(),
            $server->getId(),
            $user->getId()
        );

        // then
        $this->assertSame("EXAMPLE", $promoCode->getCode());
        $this->assertSameEnum(QuantityType::PERCENTAGE(), $promoCode->getQuantityType());
        $this->assertSame(10, $promoCode->getQuantity());
        $this->assertSame(15, $promoCode->getUsageLimit());
        $this->assertSame(0, $promoCode->getUsageCount());
        $this->assertSame(15, $promoCode->getRemainingUsage());
        $this->assertInstanceOf(DateTime::class, $promoCode->getExpiresAt());
        $this->assertSame($service->getId(), $promoCode->getServiceId());
        $this->assertSame($server->getId(), $promoCode->getServerId());
        $this->assertSame($user->getId(), $promoCode->getUserId());
    }

    /** @test */
    public function mark_promo_code_as_used()
    {
        // given
        $promoCode = $this->factory->promoCode();

        // when
        $this->promoCodeRepository->useIt($promoCode->getId());

        // then
        $freshPromoCode = $this->promoCodeRepository->get($promoCode->getId());
        $this->assertSame(1, $freshPromoCode->getUsageCount());
    }

    /** @test */
    public function deletes_promo_code()
    {
        // given
        $promoCode = $this->factory->promoCode();

        // when
        $this->promoCodeRepository->delete($promoCode->getId());

        // then
        $freshPromoCode = $this->promoCodeRepository->get($promoCode->getId());
        $this->assertNull($freshPromoCode);
    }
}
