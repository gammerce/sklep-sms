<?php
namespace Tests\Feature\Payment;

use App\Managers\PaymentModuleManager;
use App\Managers\ServiceModuleManager;
use App\Models\Purchase;
use App\Models\User;
use App\Payment\General\BillingAddress;
use App\Payment\General\PaymentMethod;
use App\Payment\General\PaymentOption;
use App\Payment\Transfer\TransferPaymentMethod;
use App\Payment\Transfer\TransferPaymentService;
use App\Repositories\PaymentTransferRepository;
use App\Requesting\Response as RequestingResponse;
use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\ServiceModules\ServiceModule;
use App\Verification\Abstracts\SupportTransfer;
use App\Verification\PaymentModules\TPay;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\TestCases\TestCase;

class TransferPaymentServiceTest extends TestCase
{
    private PaymentTransferRepository $paymentTransferRepository;
    private Purchase $purchase;
    /** @var ServiceModule|IServicePurchase  */
    private ServiceModule $serviceModule;
    private SupportTransfer $paymentModule;
    private TransferPaymentMethod $transferPaymentMethod;
    private TransferPaymentService $transferPaymentService;

    public function setUp(): void
    {
        parent::setUp();

        putenv("INFAKT_API_KEY=invalid");

        $this->transferPaymentService = $this->app->make(TransferPaymentService::class);
        $this->transferPaymentMethod = $this->app->make(TransferPaymentMethod::class);
        $this->paymentTransferRepository = $this->app->make(PaymentTransferRepository::class);

        /** @var PaymentModuleManager $paymentModuleManager */
        $paymentModuleManager = $this->app->make(PaymentModuleManager::class);

        /** @var ServiceModuleManager $serviceModuleManager */
        $serviceModuleManager = $this->app->make(ServiceModuleManager::class);

        $paymentPlatform = $this->factory->paymentPlatform([
            "module" => TPay::MODULE_ID,
        ]);

        $serviceId = "vip";
        $this->paymentModule = $paymentModuleManager->get($paymentPlatform);
        $this->serviceModule = $serviceModuleManager->get($serviceId);
        $server = $this->factory->server();
        $price = $this->factory->price([
            "service_id" => $serviceId,
            "server_id" => $server->getId(),
            "transfer_price" => 4080,
        ]);

        $this->purchase = (new Purchase(new User(), "192.0.2.1", "example"))
            ->setOrder([
                Purchase::ORDER_SERVER => $server->getId(),
                "type" => ExtraFlagType::TYPE_SID,
            ])
            ->setPaymentOption(
                new PaymentOption(PaymentMethod::TRANSFER(), $paymentPlatform->getId())
            )
            ->setUsingPrice($price)
            ->setService(
                $this->serviceModule->service->getId(),
                $this->serviceModule->service->getName()
            )
            ->setEmail("example@example.com");

        $this->purchase
            ->getPaymentSelect()
            ->setTransferPaymentPlatforms([$paymentPlatform->getId()]);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        putenv("INFAKT_API_KEY=");
    }

    /** @test */
    public function pays_with_transfer()
    {
        // when
        $paymentResult = $this->transferPaymentMethod->pay($this->purchase, $this->serviceModule);
        $finalizedPayment = $this->paymentModule->finalizeTransfer(
            Request::create("", "POST", [
                "tr_id" => "abc",
                "tr_amount" => $paymentResult->getData()["data"]["kwota"],
                "tr_crc" => $paymentResult->getData()["data"]["crc"],
                "id" => "tpay",
                "md5sum" => "xyz",
            ])
        );
        $finalizedPayment->setStatus(true); // Mark as if checking md5sum was correct
        $this->transferPaymentService->finalizePurchase($this->purchase, $finalizedPayment);

        // then
        $paymentTransfer = $this->paymentTransferRepository->get($finalizedPayment->getOrderId());
        $this->assertNotNull($paymentTransfer);
        $this->assertEqualsMoney(4080, $paymentTransfer->getIncome());
    }

    /** @test */
    public function issue_an_invoice_on_transfer_payment()
    {
        // given
        $this->purchase->setBillingAddress(
            new BillingAddress("Jan Kowalski", "", "Złota 59", "01-687", "Warszawa")
        );

        $this->requesterMock
            ->shouldReceive("post")
            ->withArgs([
                "https://api.infakt.pl/v3/invoices.json",
                json_encode([
                    "invoice" => [
                        "client_company_name" => "Jan Kowalski",
                        "client_country" => "pl",
                        "client_street" => "Złota 59",
                        "client_city" => "Warszawa",
                        "client_post_code" => "01-687",
                        "client_tax_code" => "",
                        "kind" => "vat",
                        "payment_method" => "tpay",
                        "services" => [
                            [
                                "flat_rate_tax_symbol" => null,
                                "gross_price" => 4080,
                                "name" => "VIP",
                                "symbol" => null,
                                "tax_symbol" => 0,
                            ],
                        ],
                    ],
                ]),
                [
                    "Content-Type" => "application/json",
                    "X-inFakt-ApiKey" => "invalid",
                ],
            ])
            ->andReturn(
                new RequestingResponse(
                    Response::HTTP_OK,
                    json_encode([
                        "id" => "128",
                    ])
                )
            );

        $this->requesterMock
            ->shouldReceive("post")
            ->withArgs([
                "https://api.infakt.pl/v3/invoices/128/paid.json",
                json_encode([]),
                [
                    "Content-Type" => "application/json",
                    "X-inFakt-ApiKey" => "invalid",
                ],
            ])
            ->andReturn(new RequestingResponse(Response::HTTP_NO_CONTENT, ""));

        $this->requesterMock
            ->shouldReceive("post")
            ->withArgs([
                "https://api.infakt.pl/v3/invoices/128/deliver_via_email.json",
                json_encode([
                    "print_type" => "original",
                    "locale" => "pl",
                    "recipient" => "example@example.com",
                ]),
                [
                    "Content-Type" => "application/json",
                    "X-inFakt-ApiKey" => "invalid",
                ],
            ])
            ->andReturn(new RequestingResponse(Response::HTTP_ACCEPTED, ""));

        // when
        $paymentResult = $this->transferPaymentMethod->pay($this->purchase, $this->serviceModule);
        $finalizedPayment = $this->paymentModule->finalizeTransfer(
            Request::create("", "POST", [
                "tr_id" => "abc",
                "tr_amount" => $paymentResult->getData()["data"]["kwota"],
                "tr_crc" => $paymentResult->getData()["data"]["crc"],
                "id" => "tpay",
                "md5sum" => "xyz",
            ])
        );
        $finalizedPayment->setStatus(true); // Mark as if checking md5sum was correct
        $this->transferPaymentService->finalizePurchase($this->purchase, $finalizedPayment);

        // then
        $paymentTransfer = $this->paymentTransferRepository->get($finalizedPayment->getOrderId());
        $this->assertNotNull($paymentTransfer);
    }
}
