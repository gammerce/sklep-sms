<?php
namespace App\Verification\PaymentModules;

use App\Models\SmsNumber;
use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportDirectBilling;
use App\Verification\Abstracts\SupportSms;
use App\Verification\DataField;
use App\Verification\Exceptions\BadCodeException;
use App\Verification\Exceptions\ExternalErrorException;
use App\Verification\Exceptions\NoConnectionException;
use App\Verification\Exceptions\UnknownErrorException;
use App\Verification\Exceptions\WrongCredentialsException;
use App\Verification\Results\SmsSuccessResult;

class Simpay extends PaymentModule implements SupportSms, SupportDirectBilling
{
    const MODULE_ID = "simpay";

    public static function getDataFields()
    {
        return [
            new DataField("key"),
            new DataField("secret"),
            new DataField("service_id"),
            new DataField("sms_text"),
        ];
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
            new SmsNumber("77464"),
            new SmsNumber("78464"),
            new SmsNumber("7936"),
            new SmsNumber("91055"),
            new SmsNumber("91155"),
            new SmsNumber("91455"),
            new SmsNumber("91664"),
            new SmsNumber("91955"),
            new SmsNumber("92055"),
            new SmsNumber("92555"),
        ];
    }

    public function verifySms($returnCode, $number)
    {
        $response = $this->requester->post('https://simpay.pl/api/1/status', [
            'params' => [
                'auth' => [
                    'key' => $this->getKey(),
                    'secret' => $this->getSecret(),
                ],
                'service_id' => $this->getServiceId(),
                'number' => $number,
                'code' => $returnCode,
            ],
        ]);

        if (!$response) {
            throw new NoConnectionException();
        }

        $content = $response->json();

        if (isset($content['respond']['status']) && $content['respond']['status'] == 'OK') {
            return new SmsSuccessResult(!!$content['respond']['test']);
        }

        if (isset($content['error'][0]) && is_array($content['error'][0])) {
            switch ((int) $content['error'][0]['error_code']) {
                case 103:
                case 104:
                    throw new WrongCredentialsException();

                case 404:
                case 405:
                    throw new BadCodeException();
            }

            throw new ExternalErrorException($content['error'][0]['error_name']);
        }

        throw new UnknownErrorException();
    }

    public function getSmsCode()
    {
        return $this->getData('sms_text');
    }

    private function getKey()
    {
        return $this->getData('key');
    }

    private function getSecret()
    {
        return $this->getData('secret');
    }

    private function getServiceId()
    {
        return $this->getData('service_id');
    }
}
