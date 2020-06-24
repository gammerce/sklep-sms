<?php
namespace App\Verification\Abstracts;

use App\Models\SmsNumber;
use App\Verification\Exceptions\SmsPaymentException;
use App\Verification\Results\SmsSuccessResult;

interface SupportSms
{
    /**
     * @return SmsNumber[]
     */
    public function getSmsNumbers();

    /**
     * Weryfikacja kodu zwrotnego otrzymanego poprzez wyslanie SMSa na dany numer
     *
     * @param string $returnCode kod zwrotny
     * @param string $number numer na który powinien zostać wysłany SMS
     * @return SmsSuccessResult
     * @throws SmsPaymentException
     */
    public function verifySms($returnCode, $number);

    /**
     * Zwraca kod sms, który należy wpisać w wiadomości sms
     *
     * @return string
     */
    public function getSmsCode();
}
