<?php
namespace App\Verification;

use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Exceptions\BadCodeException;
use App\Verification\Exceptions\BadNumberException;
use App\Verification\Exceptions\NoConnectionException;
use App\Verification\Exceptions\UnknownErrorException;
use App\Verification\Results\SmsSuccessResult;

class Hostplay extends PaymentModule implements SupportSms
{
    const MODULE_ID = "hostplay";

    /** @var array */
    protected $ratesNumber = [
        '0.34' => '7055',
        '0.67' => '7155',
        '1.35' => '7255',
        '2.02' => '7355',
        '2.7' => '7455',
        '3.38' => '7555',
        '4.05' => '76660',
        '6.08' => '7955',
        '6.76' => '91055',
        '7.43' => '91155',
        '9.47' => '91455',
        '12.85' => '91955',
        '13.53' => '92055',
        '16.91' => '92555',
    ];

    public function verifySms($returnCode, $number)
    {
        $response = $this->requester->get('http://hostplay.pl/api/payment/api_code_verify.php', [
            'payment' => 'homepay_sms',
            'userid' => $this->getUserId(),
            'comment' => 'SklepSMS',
            'code' => $returnCode,
        ]);

        if (!$response) {
            throw new NoConnectionException();
        }

        $content = $response->json();
        $responseNumber = $this->ratesNumber[number_format(floatval($content['kwota']), 2)];

        if (strtoupper($content['status']) === 'OK') {
            if ($responseNumber == $number) {
                return new SmsSuccessResult();
            }

            $tariffId = $this->getTariffByNumber($responseNumber)->getId();
            throw new BadNumberException($tariffId);
        }

        if (strtoupper($content['status']) === 'FAIL') {
            if (strtoupper($content['error']) === "BAD_CODE") {
                throw new BadCodeException();
            }

            if (strtoupper($content['error']) === "BAD_CODE[1]") {
                throw new BadCodeException();
            }

            if (strtoupper($content['error']) === "BAD_AMOUNT") {
                throw new BadNumberException(null);
            }

            if (strtoupper($content['error']) === "BAD_AMOUNT2") {
                throw new BadNumberException(null);
            }
        }

        throw new UnknownErrorException();
    }

    public function getSmsCode()
    {
        return $this->data['sms_text'];
    }

    private function getUserId()
    {
        return $this->data['user_id'];
    }
}
