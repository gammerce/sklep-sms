<?php
namespace App\Verification\PaymentModules;

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
    const MODULE_ID = "bizneshost";

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
        $statusExploded = explode(':', $status);

        // Bad code
        if ($statusExploded[0] == 'E') {
            throw new BadCodeException();
        }

        // Code is correct
        if ($statusExploded[0] == '1') {
            // Check whether prices are equal
            if (abs(get_sms_cost_gross($number) / 100 - floatval($statusExploded[1])) < 0.1) {
                return new SmsSuccessResult();
            }

            $tariff = $this->getTariffBySmsCostGross($statusExploded[1]);

            throw new BadNumberException($tariff !== null ? $tariff->getId() : null);
        }

        // Code used
        if ($statusExploded[0] == '2') {
            throw new BadCodeException();
        }

        // No code - $returnCode is empty
        if ($statusExploded[0] == '-1') {
            throw new BadCodeException();
        }

        // No uid
        if ($statusExploded[0] == '-2') {
            throw new InsufficientDataException();
        }

        throw new UnknownErrorException();
    }

    public function getSmsCode()
    {
        return $this->getData('sms_text');
    }

    private function getUid()
    {
        return $this->getData('uid');
    }
}
