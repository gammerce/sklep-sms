<?php
namespace App\Verification\PaymentModules;

use App\Models\FinalizedPayment;
use App\Models\Purchase;
use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportTransfer;
use App\Verification\DataField;

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

    public function prepareTransfer($price, Purchase $purchase)
    {
        $price /= 100;
        $crc = $purchase->getId();

        return [
            "url" => "https://secure.transferuj.pl",
            "method" => "POST",
            "data" => [
                "id" => $this->getAccountId(),
                "kwota" => $price,
                "opis" => $purchase->getDescription(),
                "crc" => $crc,
                "md5sum" => md5($this->getAccountId() . $price . $crc . $this->getKey()),
                "imie" => $purchase->user->getForename(),
                "nazwisko" => $purchase->user->getSurname(),
                "email" => $purchase->getEmail(),
                "pow_url" => $this->url->to("/page/tpay_success"),
                "pow_url_blad" => $this->url->to("/page/payment_error"),
                "wyn_url" => $this->url->to("/api/ipn/transfer/{$this->paymentPlatform->getId()}"),
            ]
        ];
    }

    public function finalizeTransfer(array $query, array $body)
    {
        // e.g. "40.80"
        $amount = price_to_int(array_get($body, "tr_amount"));

        return (new FinalizedPayment())
            ->setStatus($this->isPaymentValid($body))
            ->setOrderId(array_get($body, "tr_id"))
            ->setCost($amount)
            ->setIncome($amount)
            ->setTransactionId(array_get($body, "tr_crc"))
            ->setExternalServiceId(array_get($body, "id"))
            ->setTestMode(array_get($body, "test_mode", false))
            ->setOutput("TRUE");
    }

    private function isPaymentValid(array $body)
    {
        $isMd5Valid = $this->isMd5Valid(
            array_get($body, "md5sum"),
            number_format(array_get($body, "tr_amount"), 2, ".", ""),
            array_get($body, "tr_crc"),
            array_get($body, "tr_id")
        );

        return $isMd5Valid &&
            array_get($body, "tr_status") === "TRUE" &&
            array_get($body, "tr_error") === "none";
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
