<?php
namespace App\Verification\PaymentModules;

use App\Models\FinalizedPayment;
use App\Models\Purchase;
use App\Payment\General\PaymentResult;
use App\Payment\General\PaymentResultType;
use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportDirectBilling;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Abstracts\SupportTransfer;
use App\Verification\DataField;
use App\Verification\Exceptions\UnknownErrorException;
use App\Verification\Results\SmsSuccessResult;

/**
 * @link https://hotpay.pl/documentation_v3/tech_directbilling.pdf
 * @link https://hotpay.pl/documentation_v3/tech_paybylink.pdf
 * @link https://hotpay.pl/documentation_v3/tech_smspremium.pdf
 */
class HotPay extends PaymentModule implements SupportSms, SupportTransfer, SupportDirectBilling
{
    const MODULE_ID = "hotpay";

    public static function getDataFields()
    {
        return [
            // TODO Should it stay here?
            new DataField("sms_text"),
            new DataField("sms_secret"),
            new DataField("transfer_hash"),
            new DataField("transfer_secret"),
            new DataField("direct_billing_secret"),
        ];
    }

    public static function getSmsNumbers()
    {
        return [
                // TODO Implement it
            ];
    }

    public function verifySms($returnCode, $number)
    {
        $response = $this->requester->get("https://api.hotpay.pl/check_sms.php", [
            "sekret" => $this->getSmsSecret(),
            "kod_sms" => $returnCode,
        ]);

        $result = $response->json();

        if ($result["status"] === "SUKCESS") {
            return new SmsSuccessResult();
        }

        // TODO Handle different types of errors
        throw new UnknownErrorException();
    }

    public function prepareTransfer($price, Purchase $purchase)
    {
        return [
            "url" => "https://platnosc.hotpay.pl",
            "method" => "POST",
            "SEKRET" => $this->getTransferSecret(),
            "KWOTA" => $price / 100,
            "ID_ZAMOWIENIA" => $purchase->getId(),
            "EMAIL" => $purchase->getEmail(),
        ];
    }

    public function finalizeTransfer(array $query, array $body)
    {
        $amount = price_to_int(array_get($body, "KWOTA"));

        return (new FinalizedPayment())
            ->setStatus($this->isTransferValid($body))
            ->setOrderId(array_get($body, "ID_PLATNOSCI"))
            ->setCost($amount)
            ->setIncome($amount)
            ->setTransactionId(array_get($body, "ID_ZAMOWIENIA"))
            ->setTestMode(false)
            ->setOutput("OK");
    }

    public function prepareDirectBilling($price, Purchase $purchase)
    {
        return new PaymentResult(PaymentResultType::EXTERNAL(), [
            "method" => "POST",
            "url" => "https://directbilling.hotpay.pl",
            "SEKRET" => $this->getDirectBillingSecret(),
            "KWOTA" => $price / 100,
            "PRZEKIEROWANIE_SUKCESS" => $this->url->to("/page/payment_success"),
            "PRZEKIEROWANIE_BLAD" => $this->url->to("/page/payment_error"),
            "ID_ZAMOWIENIA" => $purchase->getId(),
        ]);
    }

    public function finalizeDirectBilling(array $query, array $body)
    {
        // TODO cost should not be equal income
        $cost = price_to_int(array_get($body, "KWOTA"));
        $income = price_to_int(array_get($body, "KWOTA"));

        return (new FinalizedPayment())
            ->setStatus($this->isDirectBillingValid($body))
            ->setOrderId(array_get($body, "ID_PLATNOSCI"))
            ->setCost($cost)
            ->setIncome($income)
            ->setTransactionId(array_get($body, "ID_ZAMOWIENIA"))
            ->setExternalServiceId(array_get($body, "ID_PLATNOSCI"))
            ->setTestMode(false)
            ->setOutput("OK");
    }

    public function getSmsCode()
    {
        return $this->getData("sms_text");
    }

    private function getSmsSecret()
    {
        return $this->getData("sms_secret");
    }

    private function getTransferSecret()
    {
        return $this->getData("transfer_secret");
    }

    private function getDirectBillingSecret()
    {
        return $this->getData("direct_billing_secret");
    }

    private function getTransferHash()
    {
        return $this->getData("transfer_hash");
    }

    private function isTransferValid(array $body)
    {
        $hashElements = [
            $this->getTransferHash(),
            array_get($body, "KWOTA"),
            array_get($body, "ID_PLATNOSCI"),
            array_get($body, "ID_ZAMOWIENIA"),
            array_get($body, "STATUS"),
            array_get($body, "SEKRET"),
        ];
        $hash = hash("sha256", implode(";", $hashElements));

        return $hash === array_get($body, "HASH") && array_get($body, "STATUS") === "SUCCESS";
    }

    private function isDirectBillingValid(array $body)
    {
        // TODO Improve it
        return array_get($body, "STATUS") == 1;
    }
}
