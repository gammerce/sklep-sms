<?php
namespace App\Verification;

use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Exceptions\BadCodeException;
use App\Verification\Exceptions\InsufficientDataException;
use App\Verification\Exceptions\NoConnectionException;
use App\Verification\Exceptions\UnknownErrorException;
use App\Verification\Exceptions\WrongCredentialsException;
use App\Verification\Results\SmsSuccessResult;

class Mintshost extends PaymentModule implements SupportSms
{
    protected $id = "mintshost";

    public function verifySms($returnCode, $number)
    {
        $response = $this->requester->get('https://mintshost.pl/sms2.php', [
            'kod'   => $returnCode,
            'sms'   => $number,
            'email' => $this->getEmail(),
        ]);

        if ($response === false) {
            throw new NoConnectionException();
        }

        $status = $response->getBody();

        if ($status === "1") {
            return new SmsSuccessResult();
        }

        if ($status === "0") {
            throw new BadCodeException();
        }

        if ($status === "2") {
            throw new WrongCredentialsException();
        }

        if ($status === "3") {
            throw new InsufficientDataException();
        }

        throw new UnknownErrorException();
    }

    public function getSmsCode()
    {
        return $this->data['sms_text'];
    }

    private function getEmail()
    {
        return $this->data['email'];
    }
}
