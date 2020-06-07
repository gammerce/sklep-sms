<?php
namespace App\Http\Services;

use App\Managers\ServiceModuleManager;
use App\Models\Purchase;
use App\Payment\General\PaymentMethodFactory;
use App\Payment\Interfaces\IPaymentMethod;
use App\Repositories\PaymentPlatformRepository;
use App\ServiceModules\Interfaces\IServicePromoCode;

class TransactionService
{
    /** @var ServiceModuleManager */
    private $serviceModuleManager;

    /** @var PaymentMethodFactory */
    private $paymentMethodFactory;

    /** @var PaymentPlatformRepository */
    private $paymentPlatformRepository;

    public function __construct(
        ServiceModuleManager $serviceModuleManager,
        PaymentMethodFactory $paymentMethodFactory,
        PaymentPlatformRepository $paymentPlatformRepository
    ) {
        $this->serviceModuleManager = $serviceModuleManager;
        $this->paymentMethodFactory = $paymentMethodFactory;
        $this->paymentPlatformRepository = $paymentPlatformRepository;
    }

    /**
     * @param Purchase $purchase
     * @return array
     */
    public function getTransactionDetails(Purchase $purchase)
    {
        $serviceModule = $this->serviceModuleManager->get($purchase->getServiceId());

        // TODO Go through all available payment platforms
        // Make sure it is available
        // And get its details

        $paymentPlatformIds = $purchase->getPaymentPlatformSelect()->all();
        $paymentPlatforms = $this->paymentPlatformRepository->findMany($paymentPlatformIds);

        // TODO Find a way to return all payment platforms along with wallet

        //        collect($paymentPlatforms)
        //            ->filter(function (PaymentPlatform $paymentPlatform) {
        //
        //            });

        $paymentMethods = collect($this->paymentMethodFactory->createAll())
            ->filter(function (IPaymentMethod $paymentMethod) use ($purchase) {
                return $paymentMethod->isAvailable($purchase);
            })
            ->mapWithKeys(function (IPaymentMethod $paymentMethod) use ($purchase) {
                return $paymentMethod->getPaymentDetails($purchase);
            })
            ->all();

        $output = [
            "payment_methods" => $paymentMethods,
        ];

        if ($serviceModule instanceof IServicePromoCode) {
            $promoCode = $purchase->getPromoCode();
            $output["promo_code"] = $promoCode ? $promoCode->getCode() : "";
        }

        return $output;
    }
}
