<?php
namespace App\Verification;

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
    protected $id = "pukawka";
    private $stawki = [];

    public function verifySms($returnCode, $number)
    {
        $this->tryToFetch();

        $response = $this->requester->get('https://admin.pukawka.pl/api/', [
            'keyapi' => $this->getApi(),
            'type'   => 'sms',
            'code'   => $returnCode,
        ]);

        if (!$response) {
            throw new NoConnectionException();
        }

        $get = $response->json();

        if (!empty($get)) {
            if ($get['error']) {
                if ($get['error'] === "wrong_api_key") {
                    throw new WrongCredentialsException();
                }

                throw new ExternalErrorException($get['error']);
            }

            if ($get['status'] == 'ok') {
                $kwota = str_replace(',', '.', $get['kwota']);
                foreach ($this->stawki as $s) {
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
        return $this->data['sms_text'];
    }

    private function getApi()
    {
        return $this->data['api'];
    }

    private function tryToFetch()
    {
        if (empty($this->stawki)) {
            $this->fetchStawki();
        }
    }

    private function fetchStawki()
    {
        $response = $this->requester->get('https://admin.pukawka.pl/api/', [
            'keyapi' => $this->getApi(),
            'type'   => 'sms_table',
        ]);
        $this->stawki = $response ? $response->json() : null;
    }
}
