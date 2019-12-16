<?php
namespace App\Verification;

use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Exceptions\BadCodeException;
use App\Verification\Exceptions\NoConnectionException;
use App\Verification\Exceptions\UnknownErrorException;
use App\Verification\Results\SmsSuccessResult;

class Profitsms extends PaymentModule implements SupportSms
{
    const MODULE_ID = "profitsms";

    public function verifySms($returnCode, $number)
    {
        $response = $this->requester->get('http://profitsms.pl/check.php', [
            'apiKey' => $this->getApi(),
            'code' => $returnCode,
            'smsNr' => $number,
        ]);

        if ($response === false) {
            throw new NoConnectionException();
        }

        $content = $response->getBody();
        $raport = explode('|', $content);

        switch ($raport['0']) {
            case 1:
                return new SmsSuccessResult();

            case 0:
                throw new BadCodeException();
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
