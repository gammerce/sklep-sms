<?php
namespace App\Verification\PaymentModules;

use App\Models\FinalizedPayment;
use App\Models\Purchase;
use App\Models\SmsNumber;
use App\Support\Money;
use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Abstracts\SupportTransfer;
use App\Verification\DataField;
use App\Verification\Exceptions\BadCodeException;
use App\Verification\Exceptions\BadNumberException;
use App\Verification\Exceptions\NoConnectionException;
use App\Verification\Results\SmsSuccessResult;
use Symfony\Component\HttpFoundation\Request;

class CashBill extends PaymentModule implements SupportSms, SupportTransfer
{
    const MODULE_ID = "cashbill";

    public static function getDataFields()
    {
        return [new DataField("sms_text"), new DataField("key"), new DataField("service")];
    }

    public function getSmsNumbers()
    {
        return [
            new SmsNumber("70567"),
            new SmsNumber("71480"),
            new SmsNumber("72480"),
            new SmsNumber("73480"),
            new SmsNumber("74480"),
            new SmsNumber("75480"),
            new SmsNumber("76480"),
            new SmsNumber("79480"),
            new SmsNumber("91400"),
            new SmsNumber("91900"),
            new SmsNumber("92022"),
            new SmsNumber("92550"),
        ];
    }

    public function verifySms($returnCode, $number)
    {
        $handle = fopen(
            "http://sms.cashbill.pl/backcode_check_singleuse_noip.php" .
                "?id=" .
                "&code=" .
                urlencode($this->getSmsCode()) .
                "&check=" .
                urlencode($returnCode),
            "r"
        );

        if ($handle) {
            $status = fgets($handle, 8);
            // lifetime
            fgets($handle, 24);
            fgets($handle, 96);
            $bramka = fgets($handle, 96);
            fclose($handle);

            if ($status == "0") {
                throw new BadCodeException();
            }

            if ($number !== $bramka) {
                throw new BadNumberException(get_sms_cost($bramka));
            }

            return new SmsSuccessResult();
        }

        throw new NoConnectionException();
    }

    public function prepareTransfer($price, Purchase $purchase)
    {
        $price /= 100;
        $userData = $purchase->getId();

        return [
            "url" => "https://pay.cashbill.pl/form/pay.php",
            "method" => "POST",
            "data" => [
                "service" => $this->getService(),
                "desc" => $purchase->getDescription(),
                "forname" => $purchase->user->getForename(),
                "surname" => $purchase->user->getSurname(),
                "email" => $purchase->getEmail(),
                "amount" => $price,
                "userdata" => $userData,
                "sign" => md5(
                    $this->getService() .
                        $price .
                        $purchase->getDescription() .
                        $userData .
                        $purchase->user->getForename() .
                        $purchase->user->getSurname() .
                        $purchase->getEmail() .
                        $this->getKey()
                ),
            ],
        ];
    }

    public function finalizeTransfer(Request $request)
    {
        $amount = Money::fromPrice($request->request->get("amount"));

        return (new FinalizedPayment())
            ->setStatus($this->isPaymentValid($request))
            ->setOrderId($request->request->get("orderid"))
            ->setCost($amount)
            ->setIncome($amount)
            ->setTransactionId($request->request->get("userdata"))
            ->setExternalServiceId($request->request->get("service"))
            ->setOutput("OK");
    }

    /**
     * @param Request $request
     * @return bool
     */
    private function isPaymentValid(Request $request)
    {
        return $this->checkSign($request) &&
            strtoupper($request->request->get("status")) === "OK" &&
            $request->request->get("service") == $this->getService();
    }

    /**
     * @param Request $request
     * @return bool
     */
    private function checkSign(Request $request)
    {
        $calculatedSign = md5(
            $request->request->get("service") .
                $request->request->get("orderid") .
                $request->request->get("amount") .
                $request->request->get("userdata") .
                $request->request->get("status") .
                $this->getKey()
        );
        return $calculatedSign === $request->request->get("sign");
    }

    /**
     * @return string
     */
    public function getSmsCode()
    {
        return $this->getData("sms_text");
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->getData("key");
    }

    /**
     * @return string
     */
    public function getService()
    {
        return $this->getData("service");
    }
}
