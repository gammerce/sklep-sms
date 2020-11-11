<?php
namespace App\Verification\PaymentModules;

use App\Models\FinalizedPayment;
use App\Models\Purchase;
use App\Support\Money;
use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportTransfer;
use App\Verification\DataField;
use Symfony\Component\HttpFoundation\Request;

/**
 * Created by MilyGosc.
 * @see https://forum.sklep-sms.pl/showthread.php?tid=88
 */
class TPay extends PaymentModule implements SupportTransfer
{
    const MODULE_ID = "transferuj";

    public static function getDataFields()
    {
        return [new DataField("key"), new DataField("account_id")];
    }

    public function prepareTransfer(Money $price, Purchase $purchase)
    {
        $crc = $purchase->getId();

        return [
            "url" => "https://secure.transferuj.pl",
            "method" => "POST",
            "data" => [
                "id" => $this->getAccountId(),
                "kwota" => $price->asPrice(),
                "opis" => $purchase->getDescription(),
                "crc" => $crc,
                "md5sum" => md5($this->getAccountId() . $price->asPrice() . $crc . $this->getKey()),
                "imie" => $purchase->user->getForename(),
                "nazwisko" => $purchase->user->getSurname(),
                "email" => $purchase->getEmail(),
                "pow_url" => $this->url->to("/page/tpay_success"),
                "pow_url_blad" => $this->url->to("/page/payment_error"),
                "wyn_url" => $this->url->to("/api/ipn/transfer/{$this->paymentPlatform->getId()}"),
            ],
        ];
    }

    public function finalizeTransfer(Request $request)
    {
        // e.g. "40.80"
        $amount = Money::fromPrice($request->request->get("tr_amount"));

        return (new FinalizedPayment())
            ->setStatus($this->isPaymentValid($request))
            ->setOrderId($request->request->get("tr_id"))
            ->setCost($amount)
            ->setIncome($amount)
            ->setTransactionId($request->request->get("tr_crc"))
            ->setExternalServiceId($request->request->get("id"))
            ->setTestMode($request->request->get("test_mode", false))
            ->setOutput("TRUE");
    }

    private function isPaymentValid(Request $request)
    {
        $isMd5Valid = $this->isMd5Valid(
            $request->request->get("md5sum"),
            number_format($request->request->get("tr_amount"), 2, ".", ""),
            $request->request->get("tr_crc"),
            $request->request->get("tr_id")
        );

        return $isMd5Valid &&
            $request->request->get("tr_status") === "TRUE" &&
            $request->request->get("tr_error") === "none";
    }

    private function isMd5Valid($md5sum, $transactionAmount, $crc, $transactionId)
    {
        if (!is_string($md5sum) || strlen($md5sum) !== 32) {
            return false;
        }

        $sign = md5(
            $this->getAccountId() . $transactionId . $transactionAmount . $crc . $this->getKey()
        );

        return $md5sum === $sign;
    }

    private function getKey()
    {
        return $this->getData("key");
    }

    private function getAccountId()
    {
        return $this->getData("account_id");
    }
}
