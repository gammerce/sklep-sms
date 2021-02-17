<?php
namespace App\Verification\PaymentModules;

use App\Models\SmsNumber;
use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportSms;
use App\Verification\DataField;
use App\Verification\Exceptions\BadCodeException;
use App\Verification\Exceptions\BadNumberException;
use App\Verification\Exceptions\CustomErrorException;
use App\Verification\Exceptions\NoConnectionException;
use App\Verification\Exceptions\UnknownErrorException;
use App\Verification\Exceptions\WrongCredentialsException;
use App\Verification\Results\SmsSuccessResult;

class Pukawka extends PaymentModule implements SupportSms
{
    const MODULE_ID = "pukawka";

    private array $rates = [];

    public static function getDataFields()
    {
        return [new DataField("api")];
    }

    public function getSmsNumbers()
    {
        return [
            new SmsNumber("71480", 65),
            new SmsNumber("72480", 130),
            new SmsNumber("73480", 196),
            new SmsNumber("74480", 261),
            new SmsNumber("75480", 326),
            new SmsNumber("76480", 391),
            new SmsNumber("79480", 587),
            new SmsNumber("91400", 913),
            new SmsNumber("91900", 1239),
            new SmsNumber("92550", 1630),
        ];
    }

    public function verifySms($returnCode, $number)
    {
        $this->tryToFetch();

        $response = $this->requester->get("https://admin.pukawka.pl/api/", [
            "keyapi" => $this->getApi(),
            "type" => "sms",
            "code" => $returnCode,
        ]);

        if (!$response) {
            throw new NoConnectionException();
        }

        $body = $response->json();

        if (!empty($body)) {
            if ($body["error"]) {
                if ($body["error"] === "wrong_api_key") {
                    throw new WrongCredentialsException();
                }

                throw new CustomErrorException($body["error"]);
            }

            if ($body["status"] == "ok") {
                $kwota = str_replace(",", ".", $body["kwota"]);
                foreach ($this->rates as $s) {
                    if (str_replace(",", ".", $s["wartosc"]) != $kwota) {
                        continue;
                    }

                    if ($s["numer"] == $number) {
                        return new SmsSuccessResult();
                    }

                    throw new BadNumberException(get_sms_cost($s["numer"]));
                }

                throw new UnknownErrorException();
            }

            throw new BadCodeException();
        }

        throw new UnknownErrorException();
    }

    public function getSmsCode()
    {
        return "PUKAWKA";
    }

    private function getApi()
    {
        return $this->getData("api");
    }

    private function tryToFetch()
    {
        if (empty($this->rates)) {
            $this->fetchRates();
        }
    }

    private function fetchRates()
    {
        $response = $this->requester->get("https://admin.pukawka.pl/api/", [
            "keyapi" => $this->getApi(),
            "type" => "sms_table",
        ]);
        $this->rates = $response ? $response->json() : [];
    }
}
