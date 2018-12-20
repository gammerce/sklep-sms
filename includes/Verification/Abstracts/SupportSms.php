<?php
namespace App\Verification\Abstracts;

use App\Verification\Exceptions\SmsPaymentException;

interface SupportSms
{
    const OK = 'ok';
    const BAD_CODE = 'bad_code';
    const BAD_NUMBER = 'bad_number';
    const BAD_API = 'bad_api';
    const BAD_EMAIL = 'bad_email';
    const BAD_DATA = 'bad_data';
    const SERVER_ERROR = 'server_error';
    const MISCONFIGURATION = 'misconfiguration';
    const ERROR = 'error';
    const NO_CONNECTION = 'no_connection';
    const UNKNOWN = 'unknown';

    /**
     * Weryfikacja kodu zwrotnego otrzymanego poprzez wyslanie SMSa na dany numer
     *
     * @param string $returnCode kod zwrotny
     * @param string $number numer na który powinien zostać wysłany SMS
     * @return array|null
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