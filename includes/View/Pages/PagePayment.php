<?php
namespace App\View\Pages;

use App\Payment\General\PaymentMethodFactory;
use App\Payment\General\PurchaseDataService;
use App\Payment\Interfaces\IPaymentMethod;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;

class PagePayment extends Page
{
    const PAGE_ID = "payment";

    /** @var PaymentMethodFactory */
    private $paymentMethodFactory;

    /** @var PurchaseDataService */
    private $purchaseDataService;

    public function __construct(
        PurchaseDataService $purchaseDataService,
        PaymentMethodFactory $paymentMethodFactory
    ) {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t("title_payment");
        $this->paymentMethodFactory = $paymentMethodFactory;
        $this->purchaseDataService = $purchaseDataService;
    }

    protected function content(array $query, array $body)
    {
        $transactionId = array_get($query, "tid");
        $purchase = $this->purchaseDataService->restorePurchase($transactionId);

        if (!$purchase) {
            return $this->lang->t("error_occurred");
        }

        $serviceModule = $this->heart->getServiceModule($purchase->getServiceId());
        if (!($serviceModule instanceof IServicePurchaseWeb)) {
            return $this->lang->t("bad_module");
        }

        $orderDetails = $serviceModule->orderDetails($purchase);
        $renderers = $this->paymentMethodFactory->createAll();

        $paymentMethods = collect($renderers)
            ->filter(function (IPaymentMethod $renderer) use ($purchase) {
                return $renderer->isAvailable($purchase);
            })
            ->map(function (IPaymentMethod $renderer) use ($purchase) {
                return $renderer->render($purchase);
            })
            ->join();

        return $this->template->render("payment/payment_form", [
            "orderDetails" => $orderDetails,
            "paymentMethods" => $paymentMethods,
        ]);
    }
}
