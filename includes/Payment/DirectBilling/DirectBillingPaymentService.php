<?php
namespace App\Payment\DirectBilling;

use App\Exceptions\InvalidServiceModuleException;
use App\Loggers\DatabaseLogger;
use App\Managers\ServiceModuleManager;
use App\Models\FinalizedPayment;
use App\Models\Purchase;
use App\Payment\Exceptions\InvalidPaidAmountException;
use App\Payment\Exceptions\PaymentRejectedException;
use App\Payment\General\PurchaseDataService;
use App\Repositories\PaymentDirectBillingRepository;
use App\ServiceModules\ChargeWallet\ChargeWalletServiceModule;
use App\ServiceModules\Interfaces\IServicePurchase;

class DirectBillingPaymentService
{
    /** @var DatabaseLogger */
    private $logger;

    /** @var PaymentDirectBillingRepository */
    private $paymentDirectBillingRepository;

    /** @var PurchaseDataService */
    private $purchaseDataService;

    /** @var ServiceModuleManager */
    private $serviceModuleManager;

    /** @var DirectBillingPriceService */
    private $directBillingPriceService;

    public function __construct(
        DatabaseLogger $logger,
        ServiceModuleManager $serviceModuleManager,
        PurchaseDataService $purchaseDataService,
        PaymentDirectBillingRepository $paymentDirectBillingRepository,
        DirectBillingPriceService $directBillingPriceService
    ) {
        $this->logger = $logger;
        $this->paymentDirectBillingRepository = $paymentDirectBillingRepository;
        $this->purchaseDataService = $purchaseDataService;
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
    public function finalizePurchase(Purchase $purchase, FinalizedPayment $finalizedPayment)
    {
        if (!$finalizedPayment->isSuccessful()) {
            throw new PaymentRejectedException();
        }

        if (
            $finalizedPayment->getCost() !== $this->directBillingPriceService->getPrice($purchase)
        ) {
            throw new InvalidPaidAmountException();
        }

        $serviceModule = $this->serviceModuleManager->get($purchase->getServiceId());
        if (!($serviceModule instanceof IServicePurchase)) {
            throw new InvalidServiceModuleException();
        }

        $paymentDirectBilling = $this->paymentDirectBillingRepository->create(
            $finalizedPayment->getOrderId(),
            $finalizedPayment->getIncome(),
            $finalizedPayment->getCost(),
            $purchase->user->getLastIp(),
            $purchase->user->getPlatform(),
            $finalizedPayment->isTestMode()
        );

        $purchase->setPayment([
            Purchase::PAYMENT_PAYMENT_ID => $paymentDirectBilling->getId(),
        ]);

        // TODO Move it to charge wallet module
        // Set charge amount to income value, since it was not set during the purchase process.
        // We don't know up front the income value.
        if ($serviceModule instanceof ChargeWalletServiceModule) {
            $purchase->setOrder([Purchase::ORDER_QUANTITY => $finalizedPayment->getIncome()]);
        }

        $boughtServiceId = $serviceModule->purchase($purchase);

        $this->logger->logWithUser(
            $purchase->user,
            "log_external_payment_accepted",
            $purchase->getPayment(Purchase::PAYMENT_METHOD),
            $boughtServiceId,
            $finalizedPayment->getOrderId(),
            $finalizedPayment->getCost() / 100,
            $finalizedPayment->getExternalServiceId()
        );

        $this->purchaseDataService->deletePurchase($purchase);

        return $boughtServiceId;
    }
}
