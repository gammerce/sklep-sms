<?php
namespace Tests\Feature\Http\Api\Shop;

use App\Models\PaymentPlatform;
use App\Models\Purchase;
use App\Models\User;
use App\Payment\General\BillingAddress;
use App\Payment\General\PaymentMethod;
use App\Payment\General\PurchaseDataService;
use App\PromoCode\QuantityType;
use App\Repositories\UserRepository;
use App\Requesting\Response as RequestingResponse;
use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\Verification\PaymentModules\Pukawka;
use App\Verification\PaymentModules\SimPay;
use App\Verification\PaymentModules\TPay;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\TestCases\HttpTestCase;

class PaymentResourceTest extends HttpTestCase
{
    private PurchaseDataService $purchaseDataService;
    private UserRepository $userRepository;

    private Purchase $purchase;
    private PaymentPlatform $directBillingPlatform;
    private PaymentPlatform $smsPlatform;
    private PaymentPlatform $transferPlatform;

    protected function setUp(): void
    {
        parent::setUp();

        $this->purchaseDataService = $this->app->make(PurchaseDataService::class);
        $this->userRepository = $this->app->get(UserRepository::class);

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

        $this->purchaseDataService->storePurchase($this->purchase);

        $this->requesterMock
            ->shouldReceive("post")
            ->withArgs(["https://api.infakt.pl/v3/invoices.json", Mockery::any(), Mockery::any()])
            ->andReturn(
                new RequestingResponse(
                    Response::HTTP_OK,
                    json_encode([
                        "id" => "128",
                    ])
                )
            );
    }

    /** @test */
    public function pays_with_sms_using_100_percent_promo_code()
    {
        // when
        $response = $this->post("/api/payment/{$this->purchase->getId()}", [
            "method" => PaymentMethod::SMS()->getValue(),
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
            "method" => PaymentMethod::TRANSFER()->getValue(),
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
            "method" => PaymentMethod::DIRECT_BILLING()->getValue(),
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

    /** @test */
    public function stores_user_billing_address()
    {
        // given
        $user = $this->factory->user();
        $this->purchase->user = $user;
        $this->purchaseDataService->storePurchase($this->purchase);

        // when
        $response = $this->post("/api/payment/{$this->purchase->getId()}", [
            "method" => PaymentMethod::TRANSFER()->getValue(),
            "payment_platform_id" => $this->transferPlatform->getId(),
            "billing_address_name" => "John Johny",
            "billing_address_vat_id" => "666",
            "billing_address_street" => "Sesame Street",
            "billing_address_postal_code" => "00-000",
            "billing_address_city" => "Gdansk",
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

        $freshUser = $this->userRepository->get($user->getId());
        $this->assertEquals("John Johny", $freshUser->getBillingAddress()->getName());
        $this->assertEquals("666", $freshUser->getBillingAddress()->getVatID());
        $this->assertEquals("Sesame Street", $freshUser->getBillingAddress()->getStreet());
        $this->assertEquals("00-000", $freshUser->getBillingAddress()->getPostalCode());
        $this->assertEquals("Gdansk", $freshUser->getBillingAddress()->getCity());
    }

    /** @test */
    public function user_billing_address_is_not_overwritten()
    {
        // given
        $user = $this->factory->user([
            "billing_address" => BillingAddress::fromArray(["name" => "Mr. M"]),
        ]);
        $this->purchase->user = $user;
        $this->purchaseDataService->storePurchase($this->purchase);

        // when
        $response = $this->post("/api/payment/{$this->purchase->getId()}", [
            "method" => PaymentMethod::TRANSFER()->getValue(),
            "payment_platform_id" => $this->transferPlatform->getId(),
            "billing_address_name" => "John Johny",
            "billing_address_vat_id" => "666",
            "billing_address_street" => "Sesame Street",
            "billing_address_postal_code" => "00-000",
            "billing_address_city" => "Gdansk",
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

        $freshUser = $this->userRepository->get($user->getId());
        $this->assertEquals("Mr. M", $freshUser->getBillingAddress()->getName());
        $this->assertEquals("", $freshUser->getBillingAddress()->getVatID());
        $this->assertEquals("", $freshUser->getBillingAddress()->getStreet());
        $this->assertEquals("", $freshUser->getBillingAddress()->getPostalCode());
        $this->assertEquals("", $freshUser->getBillingAddress()->getCity());
    }
}
