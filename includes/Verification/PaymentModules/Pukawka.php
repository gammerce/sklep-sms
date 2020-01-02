<?php
namespace App\Verification\PaymentModules;

use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Exceptions\BadCodeException;
use App\Verification\Exceptions\BadNumberException;
use App\Verification\Exceptions\ExternalErrorException;
use App\Verification\Exceptions\NoConnectionException;
use App\Verification\Exceptions\UnknownErrorException;
use App\Verification\Exceptions\WrongCredentialsException;
use App\Verification\Results\SmsSuccessResult;

class Pukawka extends PaymentModule implements SupportSms
{
    const MODULE_ID = "pukawka";
    private $rates = [];

    public function verifySms($returnCode, $number)
    {
        $this->tryToFetch();

        $response = $this->requester->get('https://admin.pukawka.pl/api/', [
            'keyapi' => $this->getApi(),
            'type' => 'sms',
            'code' => $returnCode,
        ]);

        if (!$response) {
            throw new NoConnectionException();
        }

        $body = $response->json();

        if (!empty($body)) {
            if ($body['error']) {
                if ($body['error'] === "wrong_api_key") {
                    throw new WrongCredentialsException();
                }

                throw new ExternalErrorException($body['error']);
            }

            if ($body['status'] == 'ok') {
                $kwota = str_replace(',', '.', $body['kwota']);
                foreach ($this->rates as $s) {
                    if (str_replace(',', '.', $s['wartosc']) != $kwota) {
                        continue;
                    }

                    if ($s['numer'] == $number) {
                        return new SmsSuccessResult();
                    }

                    $tariff = $this->getTariffByNumber($s['numer']);
                    $tariffId = $tariff !== null ? $tariff->getId() : null;

                    throw new BadNumberException($tariffId);
                }

                throw new UnknownErrorException();
            }

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

    private function tryToFetch()
    {
        if (empty($this->rates)) {
            $this->fetchRates();
        }
    }

    private function fetchRates()
    {
        $response = $this->requester->get('https://admin.pukawka.pl/api/', [
            'keyapi' => $this->getApi(),
            'type' => 'sms_table',
        ]);
        $this->rates = $response ? $response->json() : null;
    }
}
