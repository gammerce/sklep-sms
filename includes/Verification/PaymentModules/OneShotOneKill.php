<?php
namespace App\Verification\PaymentModules;

use App\Models\SmsNumber;
use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportSms;
use App\Verification\DataField;
use App\Verification\Exceptions\BadCodeException;
use App\Verification\Exceptions\BadNumberException;
use App\Verification\Exceptions\ExternalErrorException;
use App\Verification\Exceptions\NoConnectionException;
use App\Verification\Exceptions\ServerErrorException;
use App\Verification\Exceptions\UnknownErrorException;
use App\Verification\Exceptions\WrongCredentialsException;
use App\Verification\Results\SmsSuccessResult;

class OneShotOneKill extends PaymentModule implements SupportSms
{
    const MODULE_ID = "1s1k";

    private $rates = [
        '0.65' => '7136',
        '1.30' => '7255',
        '1.95' => '7355',
        '2.60' => '7455',
        '3.25' => '7555',
        '3.90' => '7636',
        '4.55' => '77464',
        '5.20' => '78464',
        '5.85' => '7936',
        '6.50' => '91055',
        '7.15' => '91155',
        '9.10' => '91455',
        '10.40' => '91664',
        '12.35' => '91955',
        '13.00' => '92055',
        '16.25' => '92555',
    ];

    public static function getDataFields()
    {
        return [new DataField("api")];
    }

    public static function getSmsNumbers()
    {
        return [
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
        $response = $this->requester->get('http://www.1shot1kill.pl/api', [
            'type' => 'sms',
            'key' => $this->getApi(),
            'sms_code' => $returnCode,
            'comment' => '',
        ]);

        if ($response === false) {
            throw new NoConnectionException();
        }

        $content = $response->json();
        if (!is_array($content)) {
            throw new ServerErrorException();
        }

        $responseNumber = $this->rates[number_format(floatval($content['amount']), 2)];

        switch ($content['status']) {
            case 'ok':
                if ($responseNumber == $number) {
                    return new SmsSuccessResult();
                }

                throw new BadNumberException(get_sms_cost($responseNumber));

            case 'fail':
                throw new BadCodeException();

            case 'error':
                switch ($content['desc']) {
                    case 'internal api error':
                        throw new ExternalErrorException();

                    case 'wrong api type':
                    case 'wrong api key':
                        throw new WrongCredentialsException();
                }

                throw new UnknownErrorException($content['desc']);

            default:
                throw new UnknownErrorException();
        }
    }

    public function getSmsCode()
    {
        return 'SHOT';
    }

    private function getApi()
    {
        return $this->getData('api');
    }
}
