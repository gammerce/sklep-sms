<?php
/**
 * Created by naXe.
 * @contact gg: 8361062
 */
namespace App\Verification\PaymentModules;

use App\Models\SmsNumber;
use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportSms;
use App\Verification\DataField;
use App\Verification\Exceptions\BadCodeException;
use App\Verification\Exceptions\ExternalErrorException;
use App\Verification\Exceptions\NoConnectionException;
use App\Verification\Exceptions\UnknownErrorException;
use App\Verification\Exceptions\WrongCredentialsException;
use App\Verification\Results\SmsSuccessResult;

class GetPay extends PaymentModule implements SupportSms
{
    const MODULE_ID = "getpay";

    public static function getDataFields(): array
    {
        return [new DataField("api"), new DataField("api_secret"), new DataField("sms_text")];
    }

    public function getSmsNumbers(): array
    {
        return [
            new SmsNumber("7143"),
            new SmsNumber("7243"),
            new SmsNumber("73550"),
            new SmsNumber("74550"),
            new SmsNumber("75550"),
            new SmsNumber("7643"),
            new SmsNumber("7943"),
            new SmsNumber("91909"),
            new SmsNumber("92505"),
        ];
    }

    public function verifySms(string $returnCode, string $number): SmsSuccessResult
    {
        $options = [
            "http" => [
                "header" => "Content-Type: application/json",
                "method" => "POST",
                "content" => json_encode([
                    "apiKey" => $this->getData("api"),
                    "apiSecret" => $this->getData("api_secret"),
                    "number" => $number,
                    "code" => $returnCode,
                    "unlimited" => false,
                ]),
            ],
        ];

        $context = stream_context_create($options);
        $result = json_decode(
            file_get_contents(
                "https://getpay.pl/panel/app/common/resource/ApiResource.php",
                false,
                $context
            )
        );

        if ($result === false) {
            throw new NoConnectionException();
        }

        switch ($result->infoCode) {
            case 200:
                return new SmsSuccessResult();

            case 400:
            case 401:
                throw new BadCodeException();

            case 402:
                throw new ExternalErrorException();

            case 102:
            case 104:
            case 105:
                throw new WrongCredentialsException();

            default:
                throw new UnknownErrorException();
        }
    }

    public function getSmsCode(): string
    {
        return (string) $this->getData("sms_text");
    }
}
