<?php
namespace App\Verification;

use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Exceptions\BadCodeException;
use App\Verification\Exceptions\NoConnectionException;
use App\Verification\Exceptions\ServerErrorException;

class Homepay extends PaymentModule implements SupportSms
{
    protected $id = 'homepay';

    public function verifySms($returnCode, $number)
    {
        $handle = fopen(
            'http://homepay.pl/API/check_code.php' .
            '?usr_id=' . urlencode($this->getApi()) .
            '&acc_id=' . urlencode($this->data[$number]) .
            '&code=' . urlencode($returnCode),
            'r'
        );

        if ($handle) {
            $status = fgets($handle, 8);
            fclose($handle);

            if ($status == '0') {
                throw new BadCodeException();
            }

            if ($status == '1') {
                return;
            }

            throw new ServerErrorException();
        }

        throw new NoConnectionException();
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