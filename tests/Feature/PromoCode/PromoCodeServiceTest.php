<?php
namespace Tests\Feature\PromoCode;

use App\Models\Purchase;
use App\Models\User;
use App\PromoCode\PromoCodeService;
use App\PromoCode\QuantityType;
use App\Repositories\PromoCodeRepository;
use App\Support\Money;
use DateTime;
use Tests\Psr4\TestCases\TestCase;

class PromoCodeServiceTest extends TestCase
{
    /** @var PromoCodeService */
    private $promoCodeService;

    /** @var PromoCodeRepository */
    private $promoCodeRepository;

    /** @var User */
    private $user;

    /** @var Purchase */
    private $purchase;

    protected function setUp()
    {
        parent::setUp();
        $this->promoCodeService = $this->app->make(PromoCodeService::class);
        $this->promoCodeRepository = $this->app->make(PromoCodeRepository::class);
        $this->user = $this->factory->user();
        $this->purchase = new Purchase($this->user, "192.0.2.1", "platform");
    }

    /** @test */
    public function finds_applicable_promo_code()
    {
        // given
        $this->factory->promoCode([
            "code" => "example",
            "service_id" => null,
        ]);

        // when
        $foundPromoCode = $this->promoCodeService->findApplicablePromoCode(
            "example",
            $this->purchase
        );

        // then
        $this->assertNotNull($foundPromoCode);
        $this->assertEquals("example", $foundPromoCode->getCode());
    }

    /** @test */
    public function cannot_find_non_existing_promo_code()
    {
        // when
        $foundPromoCode = $this->promoCodeService->findApplicablePromoCode(
            "example",
            $this->purchase
        );

        // then
        $this->assertNull($foundPromoCode);
    }

    /** @test */
    public function cannot_find_expired_promo_code()
    {
        // given
        $this->factory->promoCode([
            "code" => "example",
            "expires_at" => new DateTime("-1 minute"),
        ]);

        // when
        $foundPromoCode = $this->promoCodeService->findApplicablePromoCode(
            "example",
            $this->purchase
        );

        // then
        $this->assertNull($foundPromoCode);
    }

    /** @test */
    public function cannot_find_used_promo_code()
    {
        // given
        $promoCode = $this->factory->promoCode([
            "code" => "example",
            "usage_limit" => 1,
        ]);
        $this->promoCodeRepository->useIt($promoCode->getId());

        // when
        $foundPromoCode = $this->promoCodeService->findApplicablePromoCode(
            "example",
            $this->purchase
        );

        // then
        $this->assertNull($foundPromoCode);
    }

    /** @test */
    public function cannot_find_promo_code_assigned_to_another_user()
    {
        // given
        $this->factory->promoCode([
            "code" => "example",
            "user_id" => $this->factory->user()->getId(),
        ]);

        // when
        $foundPromoCode = $this->promoCodeService->findApplicablePromoCode(
            "example",
            $this->purchase
        );

        // then
        $this->assertNull($foundPromoCode);
    }

    /** @test */
    public function cannot_find_promo_code_assigned_to_another_server()
    {
        // given
        $this->purchase->setOrder([
            Purchase::ORDER_SERVER => $this->factory->server()->getId(),
        ]);
        $this->factory->promoCode([
            "code" => "example",
            "server_id" => $this->factory->server()->getId(),
        ]);

        // when
        $foundPromoCode = $this->promoCodeService->findApplicablePromoCode(
            "example",
            $this->purchase
        );

        // then
        $this->assertNull($foundPromoCode);
    }

    /** @test */
    public function cannot_find_promo_code_assigned_to_another_service()
    {
        // given
        $this->purchase->setServiceId("vip");
        $this->factory->promoCode([
            "code" => "example",
            "service_id" => "vippro",
        ]);

        // when
        $foundPromoCode = $this->promoCodeService->findApplicablePromoCode(
            "example",
            $this->purchase
        );

        // then
        $this->assertNull($foundPromoCode);
    }

    /** @test */
    public function apply_fixed_discount()
    {
        // given
        $promoCode = $this->factory->promoCode([
            "quantity_type" => QuantityType::FIXED(),
            "quantity" => 315,
        ]);

        // when
        $price = $this->promoCodeService->applyDiscount($promoCode, new Money(500));

        // then
        $this->assertEqualsMoney(185, $price);
    }

    /** @test */
    public function apply_percentage_discount()
    {
        // given
        $promoCode = $this->factory->promoCode([
            "quantity_type" => QuantityType::PERCENTAGE(),
            "quantity" => 70,
        ]);

        // when
        $price = $this->promoCodeService->applyDiscount($promoCode, new Money(500));

        // then
        $this->assertEqualsMoney(150, $price);
    }
}
