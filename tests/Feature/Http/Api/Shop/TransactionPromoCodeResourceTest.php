<?php
namespace Tests\Feature\Http\Api\Shop;

use App\Models\Purchase;
use App\Models\User;
use App\Payment\General\PurchaseDataService;
use App\PromoCode\QuantityType;
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
            "module" => TPay::MODULE_ID,
        ]);
        $directBillingPlatform = $this->factory->paymentPlatform([
            "module" => SimPay::MODULE_ID,
        ]);
        $smsPlatform = $this->factory->paymentPlatform([
            "module" => Cssetti::MODULE_ID,
        ]);

        $purchase = (new Purchase($user))
            ->setServiceId("vip")
            ->setPayment([
                Purchase::PAYMENT_PRICE_TRANSFER => 1000,
                Purchase::PAYMENT_PRICE_DIRECT_BILLING => 1200,
                Purchase::PAYMENT_PRICE_SMS => 2500,
            ])
            ->setPromoCode($promoCode);

        $purchase
            ->getPaymentSelect()
            ->setTransferPaymentPlatforms([$transferPlatform->getId()])
            ->setDirectBillingPaymentPlatform($directBillingPlatform->getId())
            ->setSmsPaymentPlatform($smsPlatform->getId());

        $this->purchaseDataService->storePurchase($purchase);

        // when
        $response = $this->post("/api/transactions/{$purchase->getId()}/promo_code", [
            "promo_code" => $promoCode->getCode(),
        ]);

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame(
            [
                "payment_options" => [
                    [
                        "method" => "direct_billing",
                        "payment_platform_id" => $directBillingPlatform->getId(),
                        "details" => [
                            "price" => "8.40 PLN",
                            "old_price" => "12.00",
                        ],
                    ],
                    [
                        "method" => "transfer",
                        "payment_platform_id" => $transferPlatform->getId(),
                        "details" => [
                            "price" => "7.00 PLN",
                            "old_price" => "10.00",
                        ],
                    ],
                    [
                        "method" => "wallet",
                        "payment_platform_id" => null,
                        "details" => [
                            "price" => "7.00 PLN",
                            "old_price" => "10.00",
                        ],
                    ],
                ],
                "promo_code" => $promoCode->getCode(),
            ],
            $json
        );
    }

    /** @test */
    public function apply_100_percent_promo_code()
    {
        // given
        $this->mockCSSSettiGetData();
        $user = $this->factory->user();
        $this->actingAs($user);

        $promoCode = $this->factory->promoCode([
            "quantity_type" => QuantityType::PERCENTAGE(),
            "quantity" => 100,
        ]);
        $transferPlatform = $this->factory->paymentPlatform([
            "module" => TPay::MODULE_ID,
        ]);
        $directBillingPlatform = $this->factory->paymentPlatform([
            "module" => SimPay::MODULE_ID,
        ]);
        $smsPlatform = $this->factory->paymentPlatform([
            "module" => Cssetti::MODULE_ID,
        ]);

        $purchase = (new Purchase($user))
            ->setServiceId("vip")
            ->setPayment([
                Purchase::PAYMENT_PRICE_TRANSFER => 1000,
                Purchase::PAYMENT_PRICE_DIRECT_BILLING => 1200,
                Purchase::PAYMENT_PRICE_SMS => 2500,
            ])
            ->setPromoCode($promoCode);

        $purchase
            ->getPaymentSelect()
            ->setTransferPaymentPlatforms([$transferPlatform->getId()])
            ->setDirectBillingPaymentPlatform($directBillingPlatform->getId())
            ->setSmsPaymentPlatform($smsPlatform->getId());

        $this->purchaseDataService->storePurchase($purchase);

        // when
        $response = $this->post("/api/transactions/{$purchase->getId()}/promo_code", [
            "promo_code" => $promoCode->getCode(),
        ]);

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame(
            [
                "payment_options" => [
                    [
                        "method" => "sms",
                        "payment_platform_id" => $smsPlatform->getId(),
                        "details" => [
                            "price" => "0.00 PLN",
                            "old_price" => "30.75",
                            "sms_code" => "abc123",
                            "sms_number" => null,
                        ],
                    ],
                    [
                        "method" => "direct_billing",
                        "payment_platform_id" => $directBillingPlatform->getId(),
                        "details" => [
                            "price" => "0.00 PLN",
                            "old_price" => "12.00",
                        ],
                    ],
                    [
                        "method" => "transfer",
                        "payment_platform_id" => $transferPlatform->getId(),
                        "details" => [
                            "price" => "0.00 PLN",
                            "old_price" => "10.00",
                        ],
                    ],
                    [
                        "method" => "wallet",
                        "payment_platform_id" => null,
                        "details" => [
                            "price" => "0.00 PLN",
                            "old_price" => "10.00",
                        ],
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
            Purchase::PAYMENT_PRICE_TRANSFER => 1000,
        ]);

        $purchase->getPaymentSelect()->setTransferPaymentPlatforms([$transferPlatform->getId()]);

        $this->purchaseDataService->storePurchase($purchase);

        // when
        $response = $this->post("/api/transactions/{$purchase->getId()}/promo_code", [
            "promo_code" => $promoCode->getCode(),
        ]);

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertArraySubset(
            [
                "return_id" => "warnings",
                "warnings" => [
                    "promo_code" =>
                        "<ul class=\"form_warning help is-danger\"><li >Nieprawidłowy kod promocyjny</li></ul>",
                ],
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
                Purchase::PAYMENT_PRICE_TRANSFER => 1000,
            ])
            ->setPromoCode($promoCode);

        $purchase->getPaymentSelect()->setTransferPaymentPlatforms([$transferPlatform->getId()]);

        $this->purchaseDataService->storePurchase($purchase);

        // when
        $response = $this->delete("/api/transactions/{$purchase->getId()}/promo_code");

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertSame(
            [
                "payment_options" => [
                    [
                        "method" => "transfer",
                        "payment_platform_id" => $transferPlatform->getId(),
                        "details" => [
                            "price" => "10.00 PLN",
                        ],
                    ],
                ],
                "promo_code" => "",
            ],
            $json
        );
    }
}
