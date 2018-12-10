<?php
namespace App\Verification;

use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Exceptions\BadCodeException;
use App\Verification\Exceptions\BadDataException;
use App\Verification\Exceptions\BadNumberException;
use App\Verification\Exceptions\NoConnectionException;
use App\Verification\Exceptions\UnknownException;

class Bizneshost extends PaymentModule implements SupportSms
{
    protected $serviceId = "bizneshost";

    public function verifySms($returnCode, $number)
    {
        $uid = $this->getUid();

        if (!strlen($uid)) {
            throw new BadDataException();
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
                return;
            }

            $tariff = $this->getTariffBySmsCostBrutto($status_exploded[1]);

            throw new BadNumberException(!is_null($tariff) ? $tariff->getId() : null);
        }

        // Code used
        if ($status_exploded[0] == '2') {
            throw new BadCodeException();
        }

        // No code - $return_code is empty
        if ($status_exploded[0] == '-1') {
            throw new BadCodeException();
        }

        // No uid
        if ($status_exploded[0] == '-2') {
            throw new BadDataException();
        }

        throw new UnknownException();
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