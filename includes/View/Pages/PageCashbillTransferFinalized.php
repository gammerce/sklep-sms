<?php
namespace App\View\Pages;

use App\Exceptions\InvalidConfigException;
use App\Models\Purchase;
use App\Payment\General\PurchaseInformation;
use App\System\Settings;
use App\Verification\PaymentModules\Cashbill;
use Symfony\Component\HttpFoundation\Request;

class PageCashbillTransferFinalized extends Page
{
    const PAGE_ID = "transfer_finalized";

    /** @var PurchaseInformation */
    private $purchaseInformation;

    /** @var Settings */
    private $settings;

    public function __construct(PurchaseInformation $purchaseInformation, Settings $settings)
    {
        parent::__construct();

        $this->purchaseInformation = $purchaseInformation;
        $this->settings = $settings;
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("transfer_finalized");
    }

    public function getContent(array $query, array $body)
    {
        $paymentModule = $this->heart->getPaymentModuleByPlatformId(
            $this->settings->getTransferPlatformId()
        );

        if (!($paymentModule instanceof Cashbill)) {
            throw new InvalidConfigException(
                "Invalid payment platform in shop settings [{$this->settings->getTransferPlatformId()}]."
            );
        }

        if (
            $paymentModule->checkSign($query, $paymentModule->getKey(), $query["sign"]) &&
            $query["service"] != $paymentModule->getService()
        ) {
            return $this->lang->t("transfer_unverified");
        }

        // prawidlowa sygnatura, w zaleznosci od statusu odpowiednia informacja dla klienta
        if (strtoupper($query["status"]) != "OK") {
            return $this->lang->t("transfer_error");
        }

        return $this->purchaseInformation->get([
            "payment" => Purchase::METHOD_TRANSFER,
            "payment_id" => $query["orderid"],
            "action" => "web",
        ]);
    }
}
