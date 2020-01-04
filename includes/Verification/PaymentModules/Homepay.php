<?php
namespace App\Verification\PaymentModules;

use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportSms;
use App\Verification\DataField;
use App\Verification\Exceptions\BadCodeException;
use App\Verification\Exceptions\ExternalErrorException;
use App\Verification\Exceptions\NoConnectionException;
use App\Verification\Results\SmsSuccessResult;

class Homepay extends PaymentModule implements SupportSms
{
    const MODULE_ID = 'homepay';

    public function verifySms($returnCode, $number)
    {
        $handle = fopen(
            'http://homepay.pl/API/check_code.php' .
                '?usr_id=' .
                urlencode($this->getApi()) .
                '&acc_id=' .
                urlencode($this->getData($number)) .
                '&code=' .
                urlencode($returnCode),
            'r'
        );

        if ($handle) {
            $status = fgets($handle, 8);
            fclose($handle);

            if ($status == '0') {
                throw new BadCodeException();
            }

            if ($status == '1') {
                return new SmsSuccessResult();
            }

            throw new ExternalErrorException();
        }

        throw new NoConnectionException();
    }

    public function getSmsCode()
    {
        return $this->getData('sms_text');
    }

    public function getDataFields()
    {
        return [
            new DataField("sms_text"),
            new DataField("api"),
            // TODO Implement it
        ];
    }

    private function getApi()
    {
        return $this->getData('api');
    }
}