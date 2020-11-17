<?php
namespace Tests\Feature\Http\View\Shop;

use App\Models\Purchase;
use App\Models\User;
use App\Payment\General\PurchaseDataService;
use App\Verification\PaymentModules\Pukawka;
use App\Verification\PaymentModules\SimPay;
use App\Verification\PaymentModules\TPay;
use Symfony\Component\HttpFoundation\Response;
use Tests\Psr4\TestCases\HttpTestCase;

class PaymentTest extends HttpTestCase
{
    /** @test */
    public function is_loads()
    {
        // given
        /** @var PurchaseDataService $purchaseDataService */
        $purchaseDataService = $this->app->make(PurchaseDataService::class);

        $smsPlatform = $this->factory->paymentPlatform([
            "module" => Pukawka::MODULE_ID,
        ]);
        $transferPlatform = $this->factory->paymentPlatform([
            "module" => TPay::MODULE_ID,
        ]);
        $directBillingPlatform = $this->factory->paymentPlatform([
            "module" => SimPay::MODULE_ID,
        ]);

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
            ->setTransferPaymentPlatforms([$transferPlatform->getId()])
            ->setDirectBillingPaymentPlatform($directBillingPlatform->getId())
            ->setSmsPaymentPlatform($smsPlatform->getId());

        $purchaseDataService->storePurchase($purchase);

        // when
        $response = $this->get("/page/payment", [
            "tid" => $purchase->getId(),
        ]);

        // then
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertContains("Płatność", $response->getContent());
        $this->assertContains("Szczegóły zamówienia", $response->getContent());
    }
}
