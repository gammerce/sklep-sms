<?php
namespace App\Payment\DirectBilling;

use App\Exceptions\InvalidServiceModuleException;
use App\Loggers\DatabaseLogger;
use App\Managers\ServiceModuleManager;
use App\Models\FinalizedPayment;
use App\Models\Purchase;
use App\Payment\Exceptions\InvalidPaidAmountException;
use App\Payment\Exceptions\PaymentRejectedException;
use App\Repositories\PaymentDirectBillingRepository;
use App\ServiceModules\ChargeWallet\ChargeWalletServiceModule;
use App\ServiceModules\Interfaces\IServicePurchase;

class DirectBillingPaymentService
{
    private DatabaseLogger $logger;
    private PaymentDirectBillingRepository $paymentDirectBillingRepository;
    private ServiceModuleManager $serviceModuleManager;
    private DirectBillingPriceService $directBillingPriceService;

    public function __construct(
        DatabaseLogger $logger,
        ServiceModuleManager $serviceModuleManager,
        PaymentDirectBillingRepository $paymentDirectBillingRepository,
        DirectBillingPriceService $directBillingPriceService
    ) {
        $this->logger = $logger;
        $this->paymentDirectBillingRepository = $paymentDirectBillingRepository;
        $this->serviceModuleManager = $serviceModuleManager;
        $this->directBillingPriceService = $directBillingPriceService;
    }

    /**
     * @param Purchase $purchase
     * @param FinalizedPayment $finalizedPayment
     * @return int
     * @throws InvalidPaidAmountException
     * @throws PaymentRejectedException
     * @throws InvalidServiceModuleException
     */
    public function finalizePurchase(Purchase $purchase, FinalizedPayment $finalizedPayment): int
    {
        if (!$finalizedPayment->isSuccessful()) {
            throw new PaymentRejectedException();
        }

        if (
            $finalizedPayment
                ->getCost()
                ->notEqual($this->directBillingPriceService->getPrice($purchase))
        ) {
            throw new InvalidPaidAmountException();
        }

        $serviceModule = $this->serviceModuleManager->get($purchase->getServiceId());
        if (!($serviceModule instanceof IServicePurchase)) {
            throw new InvalidServiceModuleException();
        }

        $paymentDirectBilling = $this->paymentDirectBillingRepository->create(
            $finalizedPayment->getOrderId(),
            $finalizedPayment->getIncome()->asInt(),
            $finalizedPayment->getCost()->asInt(),
            $purchase->getAddressIp(),
            $purchase->getPlatform(),
            $finalizedPayment->isTestMode()
        );

        $purchase->setPayment([
            Purchase::PAYMENT_PAYMENT_ID => $paymentDirectBilling->getId(),
        ]);

        // TODO Move it to charge wallet module
        // Set charge amount to income value, since it was not set during the purchase process.
        // We don't know up front the income value.
        if ($serviceModule instanceof ChargeWalletServiceModule) {
            $purchase->setOrder([
                Purchase::ORDER_QUANTITY => $finalizedPayment->getIncome()->asInt(),
            ]);
        }

        $boughtServiceId = $serviceModule->purchase($purchase);

        $this->logger->logWithUser(
            $purchase->user,
            "log_external_payment_accepted",
            $purchase->getPaymentOption()->getPaymentMethod(),
            $boughtServiceId,
            $finalizedPayment->getOrderId(),
            $finalizedPayment->getCost(),
            $finalizedPayment->getExternalServiceId()
        );

        return $boughtServiceId;
    }
}
