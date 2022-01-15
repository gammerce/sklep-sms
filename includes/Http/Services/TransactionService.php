<?php
namespace App\Http\Services;

use App\Managers\PaymentModuleManager;
use App\Managers\ServiceModuleManager;
use App\Models\Purchase;
use App\Payment\General\PaymentMethodFactory;
use App\Payment\Invoice\InvoiceService;
use App\Repositories\PaymentPlatformRepository;
use App\ServiceModules\Interfaces\IServicePromoCode;
use App\Verification\Abstracts\SupportTransfer;
use Generator;

class TransactionService
{
    private ServiceModuleManager $serviceModuleManager;
    private PaymentMethodFactory $paymentMethodFactory;
    private PaymentPlatformRepository $paymentPlatformRepository;
    private PaymentModuleManager $paymentModuleManager;
    private InvoiceService $invoiceService;

    public function __construct(
        ServiceModuleManager $serviceModuleManager,
        PaymentMethodFactory $paymentMethodFactory,
        PaymentModuleManager $paymentModuleManager,
        PaymentPlatformRepository $paymentPlatformRepository,
        InvoiceService $invoiceService
    ) {
        $this->serviceModuleManager = $serviceModuleManager;
        $this->paymentMethodFactory = $paymentMethodFactory;
        $this->paymentPlatformRepository = $paymentPlatformRepository;
        $this->paymentModuleManager = $paymentModuleManager;
        $this->invoiceService = $invoiceService;
    }

    public function getTransactionDetails(Purchase $purchase): array
    {
        $serviceModule = $this->serviceModuleManager->get($purchase->getServiceId());

        $output = [
            "payment_options" => iterator_to_array($this->getPaymentOptions($purchase)),
            "supports_billing_address" => $this->invoiceService->isConfigured(),
        ];

        if ($serviceModule instanceof IServicePromoCode) {
            $promoCode = $purchase->getPromoCode();
            $output["promo_code"] = $promoCode ? $promoCode->getCode() : "";
        }

        return $output;
    }

    private function getPaymentOptions(Purchase $purchase): Generator
    {
        foreach ($purchase->getPaymentSelect()->all() as $paymentOption) {
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

            yield [
                "method" => $paymentOption->getPaymentMethod(),
                "payment_platform_id" => $paymentPlatformId,
                "name" => $name,
                "details" => $paymentMethod->getPaymentDetails($purchase, $paymentPlatform),
            ];
        }
    }
}
