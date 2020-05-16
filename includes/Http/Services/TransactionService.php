<?php
namespace App\Http\Services;

use App\Managers\ServiceModuleManager;
use App\Models\Purchase;
use App\Payment\General\PaymentMethodFactory;
use App\Payment\Interfaces\IPaymentMethod;
use App\ServiceModules\Interfaces\IServicePromoCode;

class TransactionService
{
    /** @var ServiceModuleManager */
    private $serviceModuleManager;

    /** @var PaymentMethodFactory */
    private $paymentMethodFactory;

    public function __construct(
        ServiceModuleManager $serviceModuleManager,
        PaymentMethodFactory $paymentMethodFactory
    ) {
        $this->serviceModuleManager = $serviceModuleManager;
        $this->paymentMethodFactory = $paymentMethodFactory;
    }

    public function getTransactionDetails(Purchase $purchase)
    {
        $serviceModule = $this->serviceModuleManager->get($purchase->getServiceId());

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
