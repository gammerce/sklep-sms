<?php
namespace App\Verification;

use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Exceptions\BadCodeException;
use App\Verification\Exceptions\BadNumberException;
use App\Verification\Exceptions\ExternalApiException;
use App\Verification\Exceptions\NoConnectionException;
use App\Verification\Exceptions\ServerErrorException;
use App\Verification\Exceptions\WrongCredentialsException;

class Gosetti extends PaymentModule implements SupportSms
{
    const SERVICE_ID = "gosetti";

    /** @var  string */
    private $smsCode;

    /** @var array */
    private $numbers = [];

    public function verifySms($returnCode, $number)
    {
        $this->tryToFetch();

        $response = $this->requester->get('https://gosetti.pl/Api/SmsApiV2CheckCode.php', [
            'UserId' => $this->getAccountId(),
            'Code'   => $returnCode,
        ]);

        if ($response === false) {
            throw new NoConnectionException();
        }

        $content = $response->getBody();

        if (!is_numeric($content)) {
            throw new ExternalApiException();
        }

        $content = strval(floatval($content));

        if ($content == '0') {
            throw new BadCodeException();
        }

        if ($content == '-1') {
            throw new WrongCredentialsException();
        }

        if ($content == '-2' || $content == '-3') {
            throw new ServerErrorException();
        }

        if (floatval($content) > 0) {
            $expectedNumber = array_get($this->numbers, $content);

            if ($expectedNumber === null || $expectedNumber != $number) {
                $tariff = $this->getTariffByNumber($expectedNumber);
                $tariffId = $tariff ? $tariff->getId() : null;

                throw new BadNumberException($tariffId);
            }

            return;
        }

        throw new ExternalApiException();
    }

    public function getSmsCode()
    {
        return $this->smsCode;
    }

    private function getAccountId()
    {
        return $this->data['account_id'];
    }

    private function tryToFetch() {
        if (empty($this->numbers)) {
            $this->fetchNumbers();
        }
    }

    private function fetchNumbers()
    {
        $response = $this->requester->get('https://gosetti.pl/Api/SmsApiV2GetData.php');
        $data = $response ? $response->json() : null;

        // CSSetti dostarcza w feedzie kod sms
        $this->smsCode = $data['Code'];

        foreach ($data['Numbers'] as $number_data) {
            $this->numbers[strval(floatval($number_data['TopUpAmount']))] = strval($number_data['Number']);
        }
    }
}
