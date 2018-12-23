<?php
namespace App\Verification;

use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Exceptions\BadCodeException;
use App\Verification\Exceptions\BadNumberException;
use App\Verification\Exceptions\InsufficientDataException;
use App\Verification\Exceptions\NoConnectionException;
use App\Verification\Exceptions\UnknownErrorException;
use App\Verification\Results\SmsSuccessResult;

class Bizneshost extends PaymentModule implements SupportSms
{
    protected $id = "bizneshost";

    public function verifySms($returnCode, $number)
    {
        $uid = $this->getUid();

        if (!strlen($uid)) {
            throw new InsufficientDataException();
        }

        $response = $this->requester->get("http://biznes-host.pl/api/sprawdzkod_v2.php", [
            "uid" => $uid,
            "kod" => $returnCode,
        ]);

        if ($response === false) {
            throw new NoConnectionException();
        }

        $status = $response->getBody();
        $status_exploded = explode(':', $status);

        // Bad code
        if ($status_exploded[0] == 'E') {
            throw new BadCodeException();
        }

        // Code is correct
        if ($status_exploded[0] == '1') {
            // Check whether prices are equal
            if (abs(get_sms_cost_brutto($number) / 100 - floatval($status_exploded[1])) < 0.1) {
                return new SmsSuccessResult();
            }

            $tariff = $this->getTariffBySmsCostBrutto($status_exploded[1]);

            throw new BadNumberException(!is_null($tariff) ? $tariff->getId() : null);
        }

        // Code used
        if ($status_exploded[0] == '2') {
            throw new BadCodeException();
        }

        // No code - $returnCode is empty
        if ($status_exploded[0] == '-1') {
            throw new BadCodeException();
        }

        // No uid
        if ($status_exploded[0] == '-2') {
            throw new InsufficientDataException();
        }

        throw new UnknownErrorException();
    }

    public function getSmsCode()
    {
        return $this->data['sms_text'];
    }

    private function getUid()
    {
        return $this->data['uid'];
    }
}