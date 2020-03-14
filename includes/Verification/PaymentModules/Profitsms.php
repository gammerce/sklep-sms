<?php
namespace App\Verification\PaymentModules;

use App\Models\SmsNumber;
use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportSms;
use App\Verification\DataField;
use App\Verification\Exceptions\BadCodeException;
use App\Verification\Exceptions\NoConnectionException;
use App\Verification\Exceptions\UnknownErrorException;
use App\Verification\Results\SmsSuccessResult;

class Profitsms extends PaymentModule implements SupportSms
{
    const MODULE_ID = "profitsms";

    public static function getDataFields()
    {
        return [new DataField("api"), new DataField("sms_text")];
    }

    public static function getSmsNumbers()
    {
        return [
            new SmsNumber("7055"),
            new SmsNumber("7136"),
            new SmsNumber("7255"),
            new SmsNumber("7355"),
            new SmsNumber("7455"),
            new SmsNumber("7555"),
            new SmsNumber("7636"),
            new SmsNumber("7936"),
            new SmsNumber("91455"),
            new SmsNumber("91955"),
            new SmsNumber("92555"),
        ];
    }

    public function verifySms($returnCode, $number)
    {
        $response = $this->requester->get('http://profitsms.pl/check.php', [
            'apiKey' => $this->getApi(),
            'code' => $returnCode,
            'smsNr' => $number,
        ]);

        if (!$response) {
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
        return $this->getData('sms_text');
    }

    private function getApi()
    {
        return $this->getData('api');
    }
}
