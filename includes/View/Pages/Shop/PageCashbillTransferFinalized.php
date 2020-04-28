<?php
namespace App\View\Pages\Shop;

use App\Exceptions\InvalidConfigException;
use App\Models\Purchase;
use App\Payment\General\PurchaseInformation;
use App\Support\Template;
use App\System\Heart;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Verification\PaymentModules\Cashbill;
use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

class PageCashbillTransferFinalized extends Page
{
    const PAGE_ID = "transfer_finalized";

    /** @var PurchaseInformation */
    private $purchaseInformation;

    /** @var Settings */
    private $settings;

    /** @var Heart */
    private $heart;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        PurchaseInformation $purchaseInformation,
        Settings $settings,
        Heart $heart
    ) {
        parent::__construct($template, $translationManager);

        $this->purchaseInformation = $purchaseInformation;
        $this->settings = $settings;
        $this->heart = $heart;
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("transfer_finalized");
    }

    public function getContent(Request $request)
    {
        $paymentModule = $this->heart->getPaymentModuleByPlatformId(
            $this->settings->getTransferPlatformId()
        );

        if (!($paymentModule instanceof Cashbill)) {
            throw new InvalidConfigException(
                "Invalid payment platform in shop settings [{$this->settings->getTransferPlatformId()}]."
            );
        }

        $sign = $request->query->get("sign");
        $service = $request->query->get("service");
        $status = $request->query->get("status");
        $orderId = $request->query->get("orderid");

        if (
            $paymentModule->checkSign($request->query->all(), $paymentModule->getKey(), $sign) &&
            $service != $paymentModule->getService()
        ) {
            return $this->lang->t("transfer_unverified");
        }

        // prawidlowa sygnatura, w zaleznosci od statusu odpowiednia informacja dla klienta
        if (strtoupper($status) != "OK") {
            return $this->lang->t("transfer_error");
        }

        return $this->purchaseInformation->get([
            "payment" => Purchase::METHOD_TRANSFER,
            "payment_id" => $orderId,
            "action" => "web",
        ]);
    }
}
