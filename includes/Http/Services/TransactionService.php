<?php
namespace App\Http\Services;

use App\Managers\ServiceModuleManager;
use App\Models\Purchase;
use App\Payment\General\PaymentMethodFactory;
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
        $paymentOptions = $purchase->getPaymentSelect()->all();
        $paymentOptionsViews = [];

        foreach ($paymentOptions as $paymentOption) {
            $paymentMethod = $this->paymentMethodFactory->create(
                $paymentOption->getPaymentMethod()
            );
            $paymentPlatform = $this->paymentPlatformRepository->get(
                $paymentOption->getPaymentPlatformId()
            );

            if (!$paymentMethod->isAvailable($purchase, $paymentPlatform)) {
                continue;
            }

            $paymentOptionsViews[] = [
                "method" => $paymentOption->getPaymentMethod(),
                "payment_platform_id" => $paymentPlatform ? $paymentPlatform->getId() : null,
                "name" => $paymentPlatform ? $paymentPlatform->getName() : null,
                "details" => $paymentMethod->getPaymentDetails($purchase, $paymentPlatform),
            ];
        }

        $output = [
            "payment_options" => $paymentOptionsViews,
        ];

        if ($serviceModule instanceof IServicePromoCode) {
            $promoCode = $purchase->getPromoCode();
            $output["promo_code"] = $promoCode ? $promoCode->getCode() : "";
        }

        return $output;
    }
}
