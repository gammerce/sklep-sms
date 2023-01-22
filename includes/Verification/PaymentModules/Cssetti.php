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
use App\Verification\Exceptions\ServerErrorException;
use App\Verification\Exceptions\UnknownErrorException;
use App\Verification\Exceptions\WrongCredentialsException;
use App\Verification\Results\SmsSuccessResult;

/**
 * -3	Pole UserId lub Code nie zostało podane
 * -2	Pole UserId lub Code zawiera niedozwoloną wartość
 * -1	Użytkownik o podanym UserId nie został znaleziony
 * 0	Podany kod SMS jest nieprawidłowy
 * > 0	Podany kod SMS jest prawidłowy, konto zostało zasilone, a kod wykorzystany. Zwrócona wartość to kwota doładowania (TopUpAmount)
 */
class Cssetti extends PaymentModule implements SupportSms
{
    const MODULE_ID = "cssetti";

    /** @var string */
    private $smsCode;

    private array $numbers = [];

    public static function getDataFields(): array
    {
        return [new DataField("account_id")];
    }

    public function getSmsNumbers(): array
    {
        return [
            new SmsNumber("71480"),
            new SmsNumber("72480"),
            new SmsNumber("73480"),
            new SmsNumber("74480"),
            new SmsNumber("75480"),
            new SmsNumber("76480"),
            new SmsNumber("79480"),
            new SmsNumber("91400"),
            new SmsNumber("91900"),
            new SmsNumber("92521"),
        ];
    }

    public function verifySms(string $returnCode, string $number): SmsSuccessResult
    {
        $this->tryToFetchSmsData();

        $response = $this->requester->get("https://cssetti.pl/Api/SmsApiV2CheckCode.php", [
            "UserId" => $this->getAccountId(),
            "Code" => $returnCode,
        ]);

        if (!$response) {
            throw new NoConnectionException();
        }

        $content = $response->getBody();

        if (!is_numeric($content)) {
            throw new ServerErrorException();
        }

        $content = strval(floatval($content));

        if ($content == "0") {
            throw new BadCodeException();
        }

        if ($content == "-1") {
            throw new WrongCredentialsException();
        }

        if ($content == "-2" || $content == "-3") {
            throw new CustomErrorException("Pole UserId lub Code jest błędne.");
        }

        if (floatval($content) > 0) {
            $expectedNumber = array_get($this->numbers, $content);

            if ($expectedNumber === null) {
                throw new BadNumberException(null);
            }

            if ($expectedNumber != $number) {
                throw new BadNumberException(get_sms_cost($expectedNumber));
            }

            return new SmsSuccessResult();
        }

        throw new UnknownErrorException();
    }

    public function getSmsCode(): string
    {
        $this->tryToFetchSmsData();
        return $this->smsCode;
    }

    private function getAccountId(): string
    {
        return (string) $this->getData("account_id");
    }

    private function tryToFetchSmsData()
    {
        if (empty($this->numbers) || !strlen($this->smsCode)) {
            $this->fetchSmsData();
        }
    }

    private function fetchSmsData()
    {
        $response = $this->requester->get("https://cssetti.pl/Api/SmsApiV2GetData.php");

        if (!$response) {
            $this->fileLogger->error("Could not get cssetti sms data.");
            return;
        }

        $data = $response->json();

        // cssetti provides SMS code in the response
        $this->smsCode = $data["Code"];

        foreach ($data["Numbers"] as $numberData) {
            $this->numbers[strval(floatval($numberData["TopUpAmount"]))] = strval(
                $numberData["Number"]
            );
        }
    }
}
