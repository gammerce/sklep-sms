<?php
namespace App\Verification\PaymentModules;

use App\Models\SmsNumber;
use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportSms;
use App\Verification\DataField;
use App\Verification\Exceptions\BadCodeException;
use App\Verification\Exceptions\ExternalErrorException;
use App\Verification\Exceptions\NoConnectionException;
use App\Verification\Results\SmsSuccessResult;

class Homepay extends PaymentModule implements SupportSms
{
    const MODULE_ID = "homepay";

    public static function getDataFields()
    {
        return [
            new DataField("sms_text"),
            new DataField("api"),
            new DataField("7055"),
            new DataField("7155"),
            new DataField("7255"),
            new DataField("7355"),
            new DataField("7455"),
            new DataField("7555"),
            new DataField("76660"),
            new DataField("7955"),
            new DataField("91055"),
            new DataField("91155"),
            new DataField("91455"),
            new DataField("91955"),
            new DataField("92055"),
            new DataField("92520"),
        ];
    }

    public function getSmsNumbers()
    {
        return [
            new SmsNumber("7055"),
            new SmsNumber("7155"),
            new SmsNumber("7255"),
            new SmsNumber("7355"),
            new SmsNumber("7455"),
            new SmsNumber("7555"),
            new SmsNumber("76660"),
            new SmsNumber("7955"),
            new SmsNumber("91055"),
            new SmsNumber("91155"),
            new SmsNumber("91455"),
            new SmsNumber("91955"),
            new SmsNumber("92055"),
            new SmsNumber("92520"),
        ];
    }

    public function verifySms($returnCode, $number)
    {
        $handle = fopen(
            "http://homepay.pl/API/check_code.php" .
                "?usr_id=" .
                urlencode($this->getApi()) .
                "&acc_id=" .
                urlencode($this->getData($number)) .
                "&code=" .
                urlencode($returnCode),
            "r"
        );

        if ($handle) {
            $status = fgets($handle, 8);
            fclose($handle);

            if ($status == "0") {
                throw new BadCodeException();
            }

            if ($status == "1") {
                return new SmsSuccessResult();
            }

            throw new ExternalErrorException();
        }

        throw new NoConnectionException();
    }

    public function getSmsCode()
    {
        return $this->getData("sms_text");
    }

    private function getApi()
    {
        return $this->getData("api");
    }
}
