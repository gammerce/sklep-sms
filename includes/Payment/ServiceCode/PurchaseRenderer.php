<?php
namespace App\Payment\ServiceCode;

use App\Models\Purchase;
use App\Payment\Interfaces\IPurchaseRenderer;
use App\ServiceModules\Interfaces\IServiceServiceCode;
use App\Support\Template;
use App\System\Heart;

class PurchaseRenderer implements IPurchaseRenderer
{
    /** @var Template */
    private $template;

    /** @var Heart */
    private $heart;

    public function __construct(Template $template, Heart $heart)
    {
        $this->template = $template;
        $this->heart = $heart;
    }

    public function render(Purchase $purchase)
    {
        return $this->template->render("payment_method_code");
    }

    public function isAvailable(Purchase $purchase)
    {
        $serviceModule = $this->heart->getServiceModule($purchase->getService());

        return !$purchase->getPayment(Purchase::PAYMENT_DISABLED_SERVICE_CODE) &&
            $serviceModule instanceof IServiceServiceCode;
    }
}
