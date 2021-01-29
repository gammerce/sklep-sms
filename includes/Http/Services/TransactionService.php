<?php
namespace App\Http\Services;

use App\Managers\PaymentModuleManager;
use App\Managers\ServiceModuleManager;
use App\Models\Purchase;
use App\Payment\General\PaymentMethodFactory;
use App\Repositories\PaymentPlatformRepository;
use App\ServiceModules\Interfaces\IServicePromoCode;
use App\Verification\Abstracts\SupportTransfer;

class TransactionService
{
    private ServiceModuleManager $serviceModuleManager;
    private PaymentMethodFactory $paymentMethodFactory;
    private PaymentPlatformRepository $paymentPlatformRepository;
    private PaymentModuleManager $paymentModuleManager;

    public function __construct(
        ServiceModuleManager $serviceModuleManager,
        PaymentMethodFactory $paymentMethodFactory,
        PaymentModuleManager $paymentModuleManager,
        PaymentPlatformRepository $paymentPlatformRepository
    ) {
        $this->serviceModuleManager = $serviceModuleManager;
        $this->paymentMethodFactory = $paymentMethodFactory;
        $this->paymentPlatformRepository = $paymentPlatformRepository;
        $this->paymentModuleManager = $paymentModuleManager;
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

            $paymentPlatformId = null;
            $name = null;

            if ($paymentPlatform) {
                $paymentModule = $this->paymentModuleManager->get($paymentPlatform);
                if ($paymentModule instanceof SupportTransfer) {
                    $name = $paymentModule::getName();
                }

                $paymentPlatformId = $paymentPlatform->getId();
            }

            $paymentOptionsViews[] = [
                "method" => $paymentOption->getPaymentMethod(),
                "payment_platform_id" => $paymentPlatformId,
                "name" => $name,
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
