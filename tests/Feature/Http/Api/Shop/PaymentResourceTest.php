<?php
namespace Tests\Feature\Http\Api\Shop;

use App\Models\PaymentPlatform;
use App\Models\Purchase;
use App\Models\User;
use App\Payment\General\PaymentMethod;
use App\Payment\General\PurchaseDataService;
use App\PromoCode\QuantityType;
use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\Verification\PaymentModules\Pukawka;
use App\Verification\PaymentModules\SimPay;
use App\Verification\PaymentModules\TPay;
use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\TestCases\HttpTestCase;

class PaymentResourceTest extends HttpTestCase
{
    private Purchase $purchase;
    private PaymentPlatform $directBillingPlatform;
    private PaymentPlatform $smsPlatform;
    private PaymentPlatform $transferPlatform;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var PurchaseDataService $purchaseDataService */
        $purchaseDataService = $this->app->make(PurchaseDataService::class);

        $promoCode = $this->factory->promoCode([
            "code" => "MYCODE",
            "quantity_type" => QuantityType::FIXED(),
            "quantity" => 500,
        ]);
        $server = $this->factory->server();
        $this->factory->price([
            "service_id" => "vip",
            "server_id" => $server->getId(),
            "quantity" => 5,
            "sms_price" => 500,
        ]);
        $this->smsPlatform = $this->factory->paymentPlatform([
            "module" => Pukawka::MODULE_ID,
        ]);
        $this->transferPlatform = $this->factory->paymentPlatform([
            "module" => TPay::MODULE_ID,
        ]);
        $this->directBillingPlatform = $this->factory->paymentPlatform([
            "module" => SimPay::MODULE_ID,
        ]);

        $this->purchase = (new Purchase(new User(), "192.0.2.1", "example"))
            ->setService("vip", "VIP")
            ->setPayment([
                Purchase::PAYMENT_PRICE_SMS => 500,
                Purchase::PAYMENT_PRICE_TRANSFER => 500,
                Purchase::PAYMENT_PRICE_DIRECT_BILLING => 500,
            ])
            ->setOrder([
                Purchase::ORDER_QUANTITY => 5,
                Purchase::ORDER_SERVER => $server->getId(),
                "auth_data" => "my_example",
                "type" => ExtraFlagType::TYPE_SID,
            ])
            ->setPromoCode($promoCode);

        $this->purchase
            ->getPaymentSelect()
            ->setSmsPaymentPlatform($this->smsPlatform->getId())
            ->setTransferPaymentPlatforms([$this->transferPlatform->getId()])
            ->setDirectBillingPaymentPlatform($this->directBillingPlatform->getId());

        $purchaseDataService->storePurchase($this->purchase);
    }

    /** @test */
    public function pays_with_sms_using_100_percent_promo_code()
    {
        // when
        $response = $this->post("/api/payment/{$this->purchase->getId()}", [
            "method" => PaymentMethod::SMS(),
            "payment_platform_id" => $this->smsPlatform->getId(),
        ]);

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertArraySubset(
            [
                "return_id" => "purchased",
                "positive" => true,
            ],
            $json
        );
        $this->assertDatabaseHas("ss_payment_sms", [
            "code" => "",
            "income" => 0,
            "cost" => 0,
            "text" => "PUKAWKA",
            "number" => "",
            "free" => false,
        ]);
        $this->assertDatabaseHas("ss_bought_services", [
            "user_id" => 0,
            "payment" => "sms",
            "service_id" => "vip",
            "amount" => "5",
            "auth_data" => "my_example",
            "promo_code" => "MYCODE",
        ]);
    }

    /** @test */
    public function pays_with_transfer_using_100_percent_promo_code()
    {
        // when
        $response = $this->post("/api/payment/{$this->purchase->getId()}", [
            "method" => PaymentMethod::TRANSFER(),
            "payment_platform_id" => $this->transferPlatform->getId(),
        ]);

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertArraySubset(
            [
                "return_id" => "purchased",
                "positive" => true,
            ],
            $json
        );
        $this->assertDatabaseHas("ss_payment_transfer", [
            "income" => 0,
            "transfer_service" => "promo_code",
            "free" => false,
        ]);
        $this->assertDatabaseHas("ss_bought_services", [
            "user_id" => 0,
            "payment" => "transfer",
            "service_id" => "vip",
            "amount" => "5",
            "auth_data" => "my_example",
            "promo_code" => "MYCODE",
        ]);
    }

    /** @test */
    public function pays_with_direct_billing_using_100_percent_promo_code()
    {
        // when
        $response = $this->post("/api/payment/{$this->purchase->getId()}", [
            "method" => PaymentMethod::DIRECT_BILLING(),
            "payment_platform_id" => $this->directBillingPlatform->getId(),
        ]);

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $json = $this->decodeJsonResponse($response);
        $this->assertArraySubset(
            [
                "return_id" => "purchased",
                "positive" => true,
            ],
            $json
        );
        $this->assertDatabaseHas("ss_payment_direct_billing", [
            "income" => 0,
            "cost" => 0,
            "free" => false,
        ]);
        $this->assertDatabaseHas("ss_bought_services", [
            "user_id" => 0,
            "payment" => "direct_billing",
            "service_id" => "vip",
            "amount" => "5",
            "auth_data" => "my_example",
            "promo_code" => "MYCODE",
        ]);
    }
}
