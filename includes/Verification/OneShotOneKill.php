<?php
namespace App\Verification;

use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Exceptions\BadCodeException;
use App\Verification\Exceptions\BadNumberException;
use App\Verification\Exceptions\ExternalApiException;
use App\Verification\Exceptions\NoConnectionException;
use App\Verification\Exceptions\ServerErrorException;
use App\Verification\Exceptions\UnknownErrorException;
use App\Verification\Exceptions\WrongCredentialsException;

class OneShotOneKill extends PaymentModule implements SupportSms
{
    protected $id = "1s1k";

    private $rates = [
        '0.65'  => '7136',
        '1.30'  => '7255',
        '1.95'  => '7355',
        '2.60'  => '7455',
        '3.25'  => '7555',
        '3.90'  => '7636',
        '4.55'  => '77464',
        '5.20'  => '78464',
        '5.85'  => '7936',
        '6.50'  => '91055',
        '7.15'  => '91155',
        '9.10'  => '91455',
        '10.40' => '91664',
        '12.35' => '91955',
        '13.00' => '92055',
        '16.25' => '92555',
    ];

    public function verifySms($returnCode, $number)
    {
        $response = $this->requester->get('http://www.1shot1kill.pl/api', [
            'type'     => 'sms',
            'key'      => $this->getApi(),
            'sms_code' => $returnCode,
            'comment'  => '',
        ]);

        if ($response === false) {
            throw new NoConnectionException();
        }

        $content = $response->json();
        if (!is_array($content)) {
            throw new ExternalApiException();
        }

        $responseNumber = $this->rates[number_format(floatval($content['amount']), 2)];

        switch ($content['status']) {
            case 'ok':
                if ($responseNumber == $number) {
                    return;
                }

                throw new BadNumberException($this->getTariffByNumber($responseNumber)->getId());

            case 'fail':
                throw new BadCodeException();

            case 'error':
                switch ($content['desc']) {
                    case 'internal api error':
                        throw new ServerErrorException();

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
        return $this->data['sms_text'];
    }

    private function getApi()
    {
        return $this->data['api'];
    }
}