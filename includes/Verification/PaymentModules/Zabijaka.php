<?php
namespace App\Verification\PaymentModules;

use App\Models\SmsNumber;
use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportSms;
use App\Verification\DataField;
use App\Verification\Exceptions\BadCodeException;
use App\Verification\Exceptions\NoConnectionException;
use App\Verification\Exceptions\UnknownErrorException;
use App\Verification\Exceptions\WrongCredentialsException;
use App\Verification\Results\SmsSuccessResult;

class Zabijaka extends PaymentModule implements SupportSms
{
    const MODULE_ID = "zabijaka";

    public static function getDataFields()
    {
        return [new DataField("api")];
    }

    public function getSmsNumbers()
    {
        return [
            new SmsNumber("71480"),
            new SmsNumber("72480"),
            new SmsNumber("73480"),
            new SmsNumber("74480"),
            new SmsNumber("75480"),
            new SmsNumber("76480"),
            new SmsNumber("79480"),
            new SmsNumber("91400"),
            new SmsNumber("91900"),
            new SmsNumber("92550"),
        ];
    }

    public function verifySms($returnCode, $number)
    {
        $xml = simplexml_load_file(
            'http://api.zabijaka.pl/1.1' .
                '/' .
                urlencode($this->getApi()) .
                '/sms' .
                '/' .
                round(get_sms_cost($number) / 100) .
                '/' .
                urlencode($returnCode) .
                '/sms.xml/add'
        );

        if (!$xml) {
            throw new NoConnectionException();
        }

        if ($xml->error == '2') {
            throw new BadCodeException();
        }

        if ($xml->error == '1') {
            throw new WrongCredentialsException();
        }

        if ($xml->success == '1') {
            return new SmsSuccessResult();
        }

        throw new UnknownErrorException();
    }

    public function getSmsCode()
    {
        return "AG.ZABIJAKA";
    }

    private function getApi()
    {
        return $this->getData("api");
    }
}
