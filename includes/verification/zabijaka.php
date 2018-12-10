<?php

use App\PaymentModule;

class PaymentModule_Zabijaka extends PaymentModule implements SupportSms
{
    const SERVICE_ID = "zabijaka";

    /** @var  string */
    private $api;

    /** @var  string */
    private $sms_code;

    public function __construct()
    {
        parent::__construct();

        $this->api = $this->data['api'];
        $this->sms_code = $this->data['sms_text'];
    }

    public function verifySms($returnCode, $number)
    {
        $xml = simplexml_load_file(
            'http://api.zabijaka.pl/1.1' .
            '/' . urlencode($this->api) .
            '/sms' .
            '/' . round(get_sms_cost($number) / 100) .
            '/' . urlencode($returnCode) .
            '/sms.xml/add'
        );

        if (!$xml) {
            return SupportSms::NO_CONNECTION;
        }

        if ($xml->error == '2') {
            return SupportSms::BAD_CODE;
        }

        if ($xml->error == '1') {
            return SupportSms::BAD_API;
        }

        if ($xml->success == '1') {
            return SupportSms::OK;
        }

        return SupportSms::ERROR;
    }

    public function getSmsCode()
    {
        return $this->sms_code;
    }
}
