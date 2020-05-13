<?php
namespace App\Http\Controllers\Api\Shop;

use App\Exceptions\EntityNotFoundException;
use App\Exceptions\InvalidServiceModuleException;
use App\Managers\ServiceModuleManager;
use App\Payment\General\PaymentMethodFactory;
use App\Payment\General\PurchaseDataService;
use App\Payment\Interfaces\IPaymentMethod;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use Symfony\Component\HttpFoundation\JsonResponse;

class TransactionResource
{
    public function get(
        $transactionId,
        PurchaseDataService $purchaseDataService,
        ServiceModuleManager $serviceModuleManager,
        PaymentMethodFactory $paymentMethodFactory
    ) {
        $purchase = $purchaseDataService->restorePurchase($transactionId);

        if (!$purchase || $purchase->isAttempted()) {
            throw new EntityNotFoundException();
        }

        $serviceModule = $serviceModuleManager->get($purchase->getServiceId());
        if (!($serviceModule instanceof IServicePurchaseWeb)) {
            throw new InvalidServiceModuleException();
        }

        $paymentMethods = collect($paymentMethodFactory->createAll())
            ->filter(function (IPaymentMethod $paymentMethod) use ($purchase) {
                return $paymentMethod->isAvailable($purchase);
            })
            ->mapWithKeys(function (IPaymentMethod $paymentMethod) use ($purchase) {
                return $paymentMethod->getPaymentDetails($purchase);
            })
            ->all();

        return new JsonResponse($paymentMethods);
    }
}
