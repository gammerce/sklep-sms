<?php
namespace App\Verification\PaymentModules;

use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportSms;
use App\Verification\DataField;
use App\Verification\Exceptions\UnknownErrorException;
use App\Verification\Results\SmsSuccessResult;

class HotPay extends PaymentModule implements SupportSms
{
    const MODULE_ID = "hotpay";

    public static function getDataFields()
    {
        return [
            // TODO Should it stay here?
            new DataField("sms_text"),
            new DataField("secret"),
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
            "sekret" => $this->getSecret(),
            "kod_sms" => $returnCode,
        ]);

        $result = $response->json();

        if ($result["status"] === "SUKCESS") {
            return new SmsSuccessResult();
        }

        // TODO Handle different types of errors
        throw new UnknownErrorException();
    }

    public function getSmsCode()
    {
        return $this->getData("sms_text");
    }

    private function getSecret()
    {
        return $this->getData("secret");
    }
}
