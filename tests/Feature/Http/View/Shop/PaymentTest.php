<?php
namespace Tests\Feature\Http\View\Shop;

use App\Models\PaymentPlatform;
use App\Models\Purchase;
use App\Models\User;
use App\Payment\General\PurchaseDataService;
use App\ServiceModules\MybbExtraGroups\MybbExtraGroupsServiceModule;
use App\Verification\PaymentModules\Pukawka;
use App\Verification\PaymentModules\SimPay;
use App\Verification\PaymentModules\TPay;
use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\TestCases\HttpTestCase;

class PaymentTest extends HttpTestCase
{
    private PurchaseDataService $purchaseDataService;
    private PaymentPlatform $smsPlatform;
    private PaymentPlatform $transferPlatform;
    private PaymentPlatform $directBillingPlatform;

    protected function setUp(): void
    {
        parent::setUp();
        $this->purchaseDataService = $this->app->make(PurchaseDataService::class);
        $this->smsPlatform = $this->factory->paymentPlatform([
            "module" => Pukawka::MODULE_ID,
        ]);
        $this->transferPlatform = $this->factory->paymentPlatform([
            "module" => TPay::MODULE_ID,
        ]);
        $this->directBillingPlatform = $this->factory->paymentPlatform([
            "module" => SimPay::MODULE_ID,
        ]);
    }

    /** @test */
    public function is_shows_extra_flags_payment_form()
    {
        // given
        $purchase = (new Purchase(new User(), "192.0.2.1", "example"))
            ->setServiceId("vip")
            ->setOrder([
                Purchase::ORDER_SERVER => $this->factory->server()->getId(),
            ])
            ->setPayment([
                Purchase::PAYMENT_PRICE_TRANSFER => 1000,
                Purchase::PAYMENT_PRICE_DIRECT_BILLING => 1200,
                Purchase::PAYMENT_PRICE_SMS => 2500,
            ]);

        $purchase
            ->getPaymentSelect()
            ->setTransferPaymentPlatforms([$this->transferPlatform->getId()])
            ->setDirectBillingPaymentPlatform($this->directBillingPlatform->getId())
            ->setSmsPaymentPlatform($this->smsPlatform->getId());

        $this->purchaseDataService->storePurchase($purchase);

        // when
        $response = $this->get("/page/payment", [
            "tid" => $purchase->getId(),
        ]);

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString("Płatność", $response->getContent());
        $this->assertStringContainsString("Szczegóły zamówienia", $response->getContent());
    }

    /** @test */
    public function is_shows_mybb_extra_groups_payment_form()
    {
        // given
        $this->factory->service([
            "id" => "example",
            "module" => MybbExtraGroupsServiceModule::MODULE_ID,
        ]);

        $purchase = (new Purchase(new User(), "192.0.2.1", "example"))
            ->setServiceId("example")
            ->setPayment([
                Purchase::PAYMENT_PRICE_TRANSFER => 1000,
            ]);

        $purchase
            ->getPaymentSelect()
            ->setTransferPaymentPlatforms([$this->transferPlatform->getId()]);

        $this->purchaseDataService->storePurchase($purchase);

        // when
        $response = $this->get("/page/payment", [
            "tid" => $purchase->getId(),
        ]);

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString("Płatność", $response->getContent());
        $this->assertStringContainsString("Szczegóły zamówienia", $response->getContent());
    }
}
