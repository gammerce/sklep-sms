<?php

interface IPaymentSMS {

    /**
     * Weryfikacja kodu zwrotnego otrzymanego poprzez wyslanie SMSa na dany numer
     *
     * @param $sms_code - kod do weryfikacji
     * @param $sms_number - numer na który został wysłany SMS
     * @return array    ['status'] - zwracany kod
                        ['number'] - numer na który został wysłany SMS
     */
    public function verify_sms($sms_code, $sms_number);

}