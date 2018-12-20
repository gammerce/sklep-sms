<?php
namespace App\Verification;

use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Exceptions\BadCodeException;
use App\Verification\Exceptions\NoConnectionException;
use App\Verification\Exceptions\UnknownErrorException;
use App\Verification\Exceptions\WrongCredentialsException;

class Simpay extends PaymentModule implements SupportSms
{
    protected $id = "simpay";

    public function verifySms($sms_code, $sms_number)
    {
        $response = $this->requester->post('https://simpay.pl/api/1/status', [
            'params' => [
                'auth'       => [
                    'key'    => $this->getKey(),
                    'secret' => $this->getSecret(),
                ],
                'service_id' => $this->getServiceId(),
                'number'     => $sms_number,
                'code'       => $sms_code,
            ],
        ]);

        if (!$response) {
            throw new NoConnectionException();
        }

        $content = $response->json();

        if (isset($content['respond']['status']) && $content['respond']['status'] == 'OK') {
            return [
                'free' => !!$content['respond']['test'],
            ];
        }

        if (isset($content['error'][0]) && is_array($content['error'][0])) {
            switch (intval($content['error'][0]['error_code'])) {
                case 103:
                case 104:
                    throw new WrongCredentialsException();

                case 404:
                case 405:
                    throw new BadCodeException();
            }

            throw new UnknownErrorException($content['error'][0]['error_name']);
        }

        throw new UnknownErrorException();
    }

    public function getSmsCode()
    {
        return $this->data['sms_text'];
    }

    private function getKey()
    {
        return $this->data['key'];
    }

    private function getSecret()
    {
        return $this->data['secret'];
    }

    private function getServiceId()
    {
        return $this->data['service_id'];
    }
}