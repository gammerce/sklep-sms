<?php
namespace App\View\Pages\Shop;

use App\Exceptions\EntityNotFoundException;
use App\Managers\ServiceModuleManager;
use App\Payment\General\PurchaseDataService;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\Support\Template;
use App\Translation\TranslationManager;
use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

class PagePayment extends Page
{
    const PAGE_ID = "payment";

    /** @var PurchaseDataService */
    private $purchaseDataService;

    /** @var ServiceModuleManager */
    private $serviceModuleManager;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        ServiceModuleManager $serviceModuleManager,
        PurchaseDataService $purchaseDataService
    ) {
        parent::__construct($template, $translationManager);

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
            throw new EntityNotFoundException();
        }

        $serviceModule = $this->serviceModuleManager->get($purchase->getServiceId());
        if (!($serviceModule instanceof IServicePurchaseWeb)) {
            return $this->lang->t("bad_module");
        }

        $orderDetails = $serviceModule->orderDetails($purchase);

        return $this->template->render("shop/pages/payment", [
            "description" => "",
            "orderDetails" => $orderDetails,
        ]);
    }
}
