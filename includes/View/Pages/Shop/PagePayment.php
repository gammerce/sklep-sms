<?php
namespace App\View\Pages\Shop;

use App\Payment\General\PaymentMethodFactory;
use App\Payment\General\PurchaseDataService;
use App\Payment\Interfaces\IPaymentMethod;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\Support\Template;
use App\Translation\TranslationManager;
use App\View\Pages\Page;
use App\Managers\ServiceModuleManager;
use Symfony\Component\HttpFoundation\Request;

class PagePayment extends Page
{
    const PAGE_ID = "payment";

    /** @var PaymentMethodFactory */
    private $paymentMethodFactory;

    /** @var PurchaseDataService */
    private $purchaseDataService;

    /** @var ServiceModuleManager */
    private $serviceModuleManager;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        ServiceModuleManager $serviceModuleManager,
        PurchaseDataService $purchaseDataService,
        PaymentMethodFactory $paymentMethodFactory
    ) {
        parent::__construct($template, $translationManager);

        $this->paymentMethodFactory = $paymentMethodFactory;
        $this->purchaseDataService = $purchaseDataService;
        $this->serviceModuleManager = $serviceModuleManager;
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("title_payment");
    }

    public function getContent(Request $request)
    {
        $transactionId = $request->query->get("tid");
        $purchase = $this->purchaseDataService->restorePurchase($transactionId);

        if (!$purchase) {
            return $this->lang->t("error_occurred");
        }

        $serviceModule = $this->serviceModuleManager->get($purchase->getServiceId());
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
