<?php
namespace App\View\Pages\Shop;

use App\Exceptions\InvalidServiceModuleException;
use App\Loggers\DatabaseLogger;
use App\Managers\PaymentModuleManager;
use App\Payment\Exceptions\InvalidPaidAmountException;
use App\Payment\Exceptions\LackOfValidPurchaseDataException;
use App\Payment\Exceptions\PaymentRejectedException;
use App\Payment\General\ExternalPaymentService;
use App\Payment\General\PaymentMethod;
use App\Payment\General\PurchaseInformation;
use App\Payment\Transfer\TransferPaymentService;
use App\Payment\Transfer\TransferPriceService;
use App\Repositories\PaymentTransferRepository;
use App\Support\Template;
use App\Translation\TranslationManager;
use App\Verification\Abstracts\SupportTransfer;
use App\Verification\PaymentModules\PayPal;
use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

class PagePayPalApproved extends Page
{
    const PAGE_ID = "paypal_approved";

    private PurchaseInformation $purchaseInformation;
    private PaymentModuleManager $paymentModuleManager;
    private ExternalPaymentService $externalPaymentService;
    private DatabaseLogger $logger;
    private TransferPaymentService $transferPaymentService;
    private TransferPriceService $transferPriceService;
    private PaymentTransferRepository $paymentTransferRepository;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        PurchaseInformation $purchaseInformation,
        PaymentModuleManager $paymentModuleManager,
        ExternalPaymentService $externalPaymentService,
        DatabaseLogger $logger,
        TransferPaymentService $transferPaymentService,
        TransferPriceService $transferPriceService,
        PaymentTransferRepository $paymentTransferRepository
    ) {
        parent::__construct($template, $translationManager);

        $this->purchaseInformation = $purchaseInformation;
        $this->paymentModuleManager = $paymentModuleManager;
        $this->externalPaymentService = $externalPaymentService;
        $this->logger = $logger;
        $this->transferPaymentService = $transferPaymentService;
        $this->transferPriceService = $transferPriceService;
        $this->paymentTransferRepository = $paymentTransferRepository;
    }

    public function getTitle(Request $request): string
    {
        return $this->lang->t("transfer_finalized");
    }

    public function getContent(Request $request)
    {
        $token = $request->query->get("token");
        $paymentPlatformId = $request->query->get("platform");
        $paymentModule = $this->paymentModuleManager->getByPlatformId($paymentPlatformId);

        if (!$token || !($paymentModule instanceof PayPal)) {
            return $this->template->render("shop/pages/payment_error");
        }

        $paymentTransfer = $this->paymentTransferRepository->get($token);

        // Do NOT capture payment twice.
        // Additionally do NOT display purchase info for security purposes.
        if ($paymentTransfer) {
            return $this->template->render("shop/pages/payment_success", [
                "title" => $this->getTitle($request),
                "content" => "SUCCESS",
            ]);
        }

        if (!$this->finalize($paymentModule, $request)) {
            return $this->template->render("shop/pages/payment_error");
        }

        $content = $this->purchaseInformation->get([
            "payment" => PaymentMethod::TRANSFER(),
            "payment_id" => $token,
            "action" => "web",
        ]);

        return $this->template->render("shop/pages/payment_success", [
            "title" => $this->getTitle($request),
            "content" => $content,
        ]);
    }

    /**
     * @param SupportTransfer $paymentModule
     * @param Request $request
     * @return bool
     */
    private function finalize(SupportTransfer $paymentModule, Request $request)
    {
        $finalizedPayment = $paymentModule->finalizeTransfer($request);

        if (!$finalizedPayment->isSuccessful()) {
            $this->logger->log(
                "log_external_payment_not_accepted",
                PaymentMethod::TRANSFER(),
                $finalizedPayment->getOrderId(),
                $finalizedPayment->getCost(),
                $finalizedPayment->getExternalServiceId()
            );
            return false;
        }

        try {
            $purchase = $this->externalPaymentService->restorePurchase($finalizedPayment);
        } catch (LackOfValidPurchaseDataException $e) {
            $this->logger->log(
                "log_external_payment_no_transaction_file",
                $finalizedPayment->getTransactionId(),
                $finalizedPayment->getOrderId()
            );
            return false;
        }

        try {
            $this->transferPaymentService->finalizePurchase($purchase, $finalizedPayment);
        } catch (InvalidPaidAmountException $e) {
            $this->logger->log(
                "log_external_payment_invalid_amount",
                $purchase->getPaymentOption()->getPaymentMethod(),
                $finalizedPayment->getOrderId(),
                $finalizedPayment->getCost(),
                $this->transferPriceService->getPrice($purchase)
            );
            return false;
        } catch (PaymentRejectedException $e) {
            $this->logger->log(
                "log_external_payment_not_accepted",
                $purchase->getPaymentOption()->getPaymentMethod(),
                $finalizedPayment->getOrderId(),
                $finalizedPayment->getCost(),
                $finalizedPayment->getExternalServiceId()
            );
            return false;
        } catch (InvalidServiceModuleException $e) {
            $this->logger->log(
                "log_external_payment_invalid_module",
                $finalizedPayment->getOrderId(),
                $purchase->getServiceId()
            );
            return false;
        }

        return true;
    }
}
