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

class TransactionResourceTest extends HttpTestCase
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
    public function get_transaction_details()
    {
        // given
        $this->mockCSSSettiGetData();
        $user = $this->factory->user();
        $this->actingAs($user);

        $transferPlatform = $this->factory->paymentPlatform([
            "module" => TPay::MODULE_ID,
        ]);
        $directBillingPlatform = $this->factory->paymentPlatform([
            "module" => SimPay::MODULE_ID,
        ]);
        $smsPlatform = $this->factory->paymentPlatform([
            "module" => Cssetti::MODULE_ID,
        ]);

        $purchase = (new Purchase($user))->setServiceId("vip")->setPayment([
            Purchase::PAYMENT_PRICE_TRANSFER => 1000,
            Purchase::PAYMENT_PRICE_DIRECT_BILLING => 1200,
            Purchase::PAYMENT_PRICE_SMS => 2500,
        ]);

        $purchase
            ->getPaymentSelect()
            ->setSmsPaymentPlatform($smsPlatform->getId())
            ->setTransferPaymentPlatforms([$transferPlatform->getId()])
            ->setDirectBillingPaymentPlatform($directBillingPlatform->getId());

        $this->purchaseDataService->storePurchase($purchase);

        // when
        $response = $this->get("/api/transactions/{$purchase->getId()}");

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
                            "price" => "30.75 PLN",
                            "sms_code" => "abc123",
                            "sms_number" => "92521",
                        ],
                    ],
                    [
                        "method" => "direct_billing",
                        "payment_platform_id" => $directBillingPlatform->getId(),
                        "details" => [
                            "price" => "12.00 PLN",
                        ],
                    ],
                    [
                        "method" => "transfer",
                        "payment_platform_id" => $transferPlatform->getId(),
                        "details" => [
                            "price" => "10.00 PLN",
                        ],
                    ],
                    [
                        "method" => "wallet",
                        "payment_platform_id" => null,
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

    /** @test */
    public function get_transaction_details_with_promo_code_applied()
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
            ->setSmsPaymentPlatform($smsPlatform->getId())
            ->setTransferPaymentPlatforms([$transferPlatform->getId()])
            ->setDirectBillingPaymentPlatform($directBillingPlatform->getId());

        $this->purchaseDataService->storePurchase($purchase);

        // when
        $response = $this->get("/api/transactions/{$purchase->getId()}");

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
    public function get_transaction_details_without_user()
    {
        // given
        $this->mockCSSSettiGetData();

        $transferPlatform = $this->factory->paymentPlatform([
            "module" => TPay::MODULE_ID,
        ]);
        $directBillingPlatform = $this->factory->paymentPlatform([
            "module" => SimPay::MODULE_ID,
        ]);
        $smsPlatform = $this->factory->paymentPlatform([
            "module" => Cssetti::MODULE_ID,
        ]);

        $purchase = (new Purchase(new User()))->setServiceId("vip")->setPayment([
            Purchase::PAYMENT_PRICE_TRANSFER => 1000,
            Purchase::PAYMENT_PRICE_DIRECT_BILLING => 1200,
            Purchase::PAYMENT_PRICE_SMS => 2500,
        ]);

        $purchase
            ->getPaymentSelect()
            ->setSmsPaymentPlatform($smsPlatform->getId())
            ->setTransferPaymentPlatforms([$transferPlatform->getId()])
            ->setDirectBillingPaymentPlatform($directBillingPlatform->getId());

        $this->purchaseDataService->storePurchase($purchase);

        // when
        $response = $this->get("/api/transactions/{$purchase->getId()}");

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
                            "price" => "30.75 PLN",
                            "sms_code" => "abc123",
                            "sms_number" => "92521",
                        ],
                    ],
                    [
                        "method" => "direct_billing",
                        "payment_platform_id" => $directBillingPlatform->getId(),
                        "details" => [
                            "price" => "12.00 PLN",
                        ],
                    ],
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
