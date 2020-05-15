<?php
namespace Tests\Feature\Http\Api\Shop;

use App\Models\Purchase;
use App\Models\User;
use App\Payment\General\PurchaseDataService;
use App\Verification\PaymentModules\Cssetti;
use App\Verification\PaymentModules\SimPay;
use App\Verification\PaymentModules\TPay;
use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\Concerns\CssettiConcern;
use Tests\Psr4\TestCases\HttpTestCase;

class TransactionPromoCodeResourceTest extends HttpTestCase
{
    use CssettiConcern;

    /** @var PurchaseDataService */
    private $purchaseDataService;

    protected function setUp()
    {
        parent::setUp();
        $this->purchaseDataService = $this->app->make(PurchaseDataService::class);
    }

    /** @test */
    public function apply_promo_code()
    {
        // given
        $this->mockCSSSettiGetData();
        $user = $this->factory->user();
        $this->actingAs($user);

        $promoCode = $this->factory->promoCode();
        $transferPlatform = $this->factory->paymentPlatform([
            "module" => TPay::class,
        ]);
        $directBillingPlatform = $this->factory->paymentPlatform([
            "module" => SimPay::class,
        ]);
        $smsPlatform = $this->factory->paymentPlatform([
            "module" => Cssetti::MODULE_ID,
        ]);

        $purchase = (new Purchase($user))
            ->setServiceId("vip")
            ->setPayment([
                Purchase::PAYMENT_PLATFORM_TRANSFER => $transferPlatform->getId(),
                Purchase::PAYMENT_PLATFORM_DIRECT_BILLING => $directBillingPlatform->getId(),
                Purchase::PAYMENT_PLATFORM_SMS => $smsPlatform->getId(),
                Purchase::PAYMENT_PRICE_TRANSFER => 1000,
                Purchase::PAYMENT_PRICE_DIRECT_BILLING => 1200,
                Purchase::PAYMENT_PRICE_SMS => 2500,
            ])
            ->setPromoCode($promoCode);

        $this->purchaseDataService->storePurchase($purchase);

        // when
        $response = $this->post(
            "/api/transactions/{$purchase->getId()}/promo_code/{$promoCode->getCode()}"
        );

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame(
            [
                "payment_methods" => [
                    "sms" => [
                        "price_gross" => "30.75 PLN",
                        "sms_code" => "abc123",
                        "sms_number" => "92521",
                    ],
                    "direct_billing" => [
                        "price" => "8.40 PLN",
                        "old_price" => "12.00",
                    ],
                    "transfer" => [
                        "price" => "7.00 PLN",
                        "old_price" => "10.00",
                    ],
                    "wallet" => [
                        "price" => "7.00 PLN",
                        "old_price" => "10.00",
                    ],
                ],
                "promo_code" => $promoCode->getCode(),
            ],
            $json
        );
    }

    /** @test */
    public function coupon_is_not_applicable()
    {
        // given
        $user = $this->factory->user();
        $promoCode = $this->factory->promoCode([
            "user_id" => $user->getId(),
        ]);

        $transferPlatform = $this->factory->paymentPlatform([
            "module" => TPay::MODULE_ID,
        ]);

        $purchase = (new Purchase(new User()))->setServiceId("vip")->setPayment([
            Purchase::PAYMENT_PLATFORM_TRANSFER => $transferPlatform->getId(),
            Purchase::PAYMENT_PRICE_TRANSFER => 1000,
        ]);

        $this->purchaseDataService->storePurchase($purchase);

        // when
        $response = $this->post(
            "/api/transactions/{$purchase->getId()}/promo_code/{$promoCode->getCode()}"
        );

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame(
            [
                "return_id" => "error",
                "text" => "Nieprawidłowy kod promocyjny",
                "positive" => false,
            ],
            $json
        );
    }

    /** @test */
    public function removes_coupon()
    {
        // given
        $transferPlatform = $this->factory->paymentPlatform([
            "module" => TPay::MODULE_ID,
        ]);
        $promoCode = $this->factory->promoCode();

        $purchase = (new Purchase(new User()))
            ->setServiceId("vip")
            ->setPayment([
                Purchase::PAYMENT_PLATFORM_TRANSFER => $transferPlatform->getId(),
                Purchase::PAYMENT_PRICE_TRANSFER => 1000,
            ])
            ->setPromoCode($promoCode);

        $this->purchaseDataService->storePurchase($purchase);

        // when
        $response = $this->delete("/api/transactions/{$purchase->getId()}/promo_code");

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame(
            [
                "payment_methods" => [
                    "transfer" => [
                        "price" => "10.00 PLN",
                    ],
                ],
                "promo_code" => "",
            ],
            $json
        );
    }
}
