<?php
namespace Tests\Feature\Http\View\Shop;

use App\Models\PaymentPlatform;
use App\Models\Purchase;
use App\Payment\General\PaymentMethod;
use App\Payment\General\PaymentOption;
use App\Payment\General\PurchaseDataService;
use App\Requesting\Response as RequestingResponse;
use App\Verification\PaymentModules\PayPal;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\TestCases\HttpTestCase;

class PayPalApprovedTest extends HttpTestCase
{
    /** @var PurchaseDataService */
    private $purchaseDataService;

    /** @var PaymentPlatform */
    private $payPalPlatform;

    protected function setUp()
    {
        parent::setUp();
        $this->purchaseDataService = $this->app->make(PurchaseDataService::class);
        $this->payPalPlatform = $this->factory->paymentPlatform([
            "module" => PayPal::MODULE_ID,
            "data" => [
                "client_id" => "bar",
                "secret" => "foo",
            ],
        ]);
    }

    /** @test */
    public function finalize_paypal_payment()
    {
        // given
        $paymentToken = "abc123";

        $user = $this->factory->user();
        $purchase = (new Purchase($user, "example"))
            ->setServiceId("charge_wallet")
            ->setPayment([
                Purchase::PAYMENT_PRICE_TRANSFER => 500,
            ])
            ->setOrder([
                Purchase::ORDER_QUANTITY => 500,
            ])
            ->setPaymentOption(
                new PaymentOption(PaymentMethod::TRANSFER(), $this->payPalPlatform->getId())
            );

        $this->purchaseDataService->storePurchase($purchase);

        $this->requesterMock
            ->shouldReceive("post")
            ->withArgs([
                "https://api.paypal.com/v2/checkout/orders/{$paymentToken}/capture",
                [],
                Mockery::any(),
            ])
            ->andReturn(
                new RequestingResponse(
                    Response::HTTP_OK,
                    json_encode([
                        "status" => "COMPLETED",
                        "purchase_units" => [
                            [
                                "payments" => [
                                    "captures" => [
                                        [
                                            "seller_receivable_breakdown" => [
                                                "gross_amount" => [
                                                    "value" => "5.00",
                                                ],
                                                "net_amount" => [
                                                    "value" => "4.50",
                                                ],
                                            ],
                                            "custom_id" => $purchase->getId(),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ])
                )
            );

        // when
        $response = $this->get("/page/paypal_approved", [
            "token" => $paymentToken,
            "platform" => $this->payPalPlatform->getId(),
        ]);

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertContains(
            "Dziękujemy! Twoja płatność zakończyła się pomyślnie.",
            $response->getContent()
        );

        $this->assertDatabaseHas("ss_users", [
            "uid" => $user->getId(),
            "wallet" => 500,
        ]);
    }

    /** @test */
    public function invalid_payment_token()
    {
        // given
        $paymentToken = "example";

        $this->requesterMock
            ->shouldReceive("post")
            ->withArgs([
                "https://api.paypal.com/v2/checkout/orders/{$paymentToken}/capture",
                [],
                Mockery::any(),
            ])
            ->andReturn(
                new RequestingResponse(
                    Response::HTTP_OK,
                    json_encode([
                        "status" => "REJECTED",
                    ])
                )
            );

        // when
        $response = $this->get("/page/paypal_approved", [
            "token" => $paymentToken,
            "platform" => $this->payPalPlatform->getId(),
        ]);

        // then
        $this->assertContains("Operator zwrócił błąd transakcji.", $response->getContent());
    }
}
