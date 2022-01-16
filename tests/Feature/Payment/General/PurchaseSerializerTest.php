<?php

namespace Tests\Feature\Payment\General;

use App\Models\Purchase;
use App\Payment\General\PaymentMethod;
use App\Payment\General\PaymentOption;
use App\Payment\General\PurchaseSerializer;
use App\Repositories\PromoCodeRepository;
use App\Repositories\UserRepository;
use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\Verification\PaymentModules\Cssetti;
use App\Verification\PaymentModules\SimPay;
use App\Verification\PaymentModules\TPay;
use Tests\Psr4\TestCases\TestCase;

class PurchaseSerializerTest extends TestCase
{
    private PurchaseSerializer $purchaseSerializer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->purchaseSerializer = $this->app->make(PurchaseSerializer::class);
    }

    /** @test */
    public function serializes_and_deserializes_purchase()
    {
        // given
        $purchase = $this->createComplexPurchase();

        // when
        $serializedPurchase = $this->purchaseSerializer->serialize($purchase);
        $deserializedPurchase = $this->purchaseSerializer->deserialize($serializedPurchase);

        // then
        $this->assertEquals($purchase, $deserializedPurchase);
    }

    /** @test */
    public function user_is_refreshed_on_deserialization()
    {
        // given
        /** @var UserRepository $userRepository */
        $userRepository = $this->app->make(UserRepository::class);

        $user = $this->factory->user();
        $purchase = new Purchase($user, "192.0.2.1", "example");

        $serializedPurchase = $this->purchaseSerializer->serialize($purchase);
        $user->setWallet(56);
        $userRepository->update($user);

        // when
        $deserializedPurchase = $this->purchaseSerializer->deserialize($serializedPurchase);

        // then
        $this->assertEqualsMoney(56, $deserializedPurchase->user->getWallet());
    }

    /** @test */
    public function promo_code_is_refreshed_on_deserialization()
    {
        // given
        /** @var PromoCodeRepository $promoCodeRepository */
        $promoCodeRepository = $this->app->make(PromoCodeRepository::class);

        $user = $this->factory->user();
        $promoCode = $this->factory->promoCode([
            "usage_limit" => 1,
        ]);
        $purchase = new Purchase($user, "192.0.2.1", "example");
        $purchase->setPromoCode($promoCode);

        $serializedPurchase = $this->purchaseSerializer->serialize($purchase);
        $promoCodeRepository->useIt($promoCode->getId());

        // when
        $deserializedPurchase = $this->purchaseSerializer->deserialize($serializedPurchase);

        // then
        $this->assertNull($deserializedPurchase->getPromoCode());
    }

    private function createComplexPurchase()
    {
        $user = $this->factory->user();
        $server = $this->factory->server();
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

        $purchase = (new Purchase($user, "192.0.2.1", "example"))
            ->setPromoCode($promoCode)
            ->setPaymentOption(new PaymentOption(PaymentMethod::SMS(), $smsPlatform->getId()))
            ->setPayment([
                Purchase::PAYMENT_PRICE_TRANSFER => 1000,
                Purchase::PAYMENT_PRICE_DIRECT_BILLING => 1200,
                Purchase::PAYMENT_PRICE_SMS => 2500,
            ])
            ->setOrder([
                Purchase::ORDER_SERVER => $server->getId(),
                "type" => ExtraFlagType::TYPE_SID,
            ])
            ->setService("vip", "VIP")
            ->setEmail("example@example.com");

        $purchase
            ->getPaymentSelect()
            ->setSmsPaymentPlatform($smsPlatform->getId())
            ->setTransferPaymentPlatforms([$transferPlatform->getId()])
            ->setDirectBillingPaymentPlatform($directBillingPlatform->getId());

        return $purchase;
    }
}
