<?php
namespace App\Verification\PaymentModules;

use App\Models\SmsNumber;
use App\Support\Money;
use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportSms;
use App\Verification\DataField;
use App\Verification\Exceptions\BadCodeException;
use App\Verification\Exceptions\BadNumberException;
use App\Verification\Exceptions\NoConnectionException;
use App\Verification\Exceptions\UnknownErrorException;
use App\Verification\Results\SmsSuccessResult;

class Hostplay extends PaymentModule implements SupportSms
{
    const MODULE_ID = "hostplay";

    public static function getDataFields()
    {
        return [new DataField("user_id")];
    }

    public function getSmsNumbers()
    {
        return [
            new SmsNumber("7055", 34),
            new SmsNumber("7155", 67),
            new SmsNumber("7255", 135),
            new SmsNumber("7355", 202),
            new SmsNumber("7455", 270),
            new SmsNumber("7555", 338),
            new SmsNumber("76660", 405),
            new SmsNumber("7955", 608),
            new SmsNumber("91055", 676),
            new SmsNumber("91155", 743),
            new SmsNumber("91455", 947),
            new SmsNumber("91955", 1285),
            new SmsNumber("92055", 1353),
            new SmsNumber("92555", 1691),
        ];
    }

    public function verifySms($returnCode, $number)
    {
        $response = $this->requester->get("http://hostplay.pl/api/payment/api_code_verify.php", [
            "payment" => "homepay_sms",
            "userid" => $this->getUserId(),
            "comment" => "SklepSMS",
            "code" => $returnCode,
        ]);

        if (!$response) {
            throw new NoConnectionException();
        }

        $content = $response->json();
        $responseNumber = $this->getSmsNumberByProvision(Money::fromPrice($content["kwota"]));

        if (strtoupper($content["status"]) === "OK") {
            if ($responseNumber == $number) {
                return new SmsSuccessResult();
            }

            throw new BadNumberException(get_sms_cost($responseNumber));
        }

        if (strtoupper($content["status"]) === "FAIL") {
            if (strtoupper($content["error"]) === "BAD_CODE") {
                throw new BadCodeException();
            }

            if (strtoupper($content["error"]) === "BAD_CODE[1]") {
                throw new BadCodeException();
            }

            if (strtoupper($content["error"]) === "BAD_AMOUNT") {
                throw new BadNumberException(null);
            }

            if (strtoupper($content["error"]) === "BAD_AMOUNT2") {
                throw new BadNumberException(null);
            }
        }

        throw new UnknownErrorException();
    }

    public function getSmsCode()
    {
        return "HOSTPLAY";
    }

    private function getUserId()
    {
        return $this->getData("user_id");
    }

    /**
     * @param Money $price
     * @return string|null
     */
    private function getSmsNumberByProvision(Money $price)
    {
        foreach ($this->getSmsNumbers() as $smsNumber) {
            if ($smsNumber->getProvision()->equals($price)) {
                return $smsNumber->getNumber();
            }
        }

        return null;
    }
}
