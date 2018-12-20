<?php
namespace App\Verification;

use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Exceptions\BadCodeException;
use App\Verification\Exceptions\NoConnectionException;
use App\Verification\Exceptions\UnknownErrorException;
use App\Verification\Exceptions\WrongCredentialsException;

class Zabijaka extends PaymentModule implements SupportSms
{
    protected $id = "zabijaka";

    public function verifySms($returnCode, $number)
    {
        $xml = simplexml_load_file(
            'http://api.zabijaka.pl/1.1' .
            '/' . urlencode($this->getApi()) .
            '/sms' .
            '/' . round(get_sms_cost($number) / 100) .
            '/' . urlencode($returnCode) .
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
            return;
        }

        throw new UnknownErrorException();
    }

    public function getSmsCode()
    {
        return $this->data['sms_text'];
    }

    private function getApi()
    {
        return $this->data['api'];
    }
}
