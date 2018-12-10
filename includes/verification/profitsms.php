<?php

use App\PaymentModule;

class PaymentModule_Profitsms extends PaymentModule implements SupportSms
{
    const SERVICE_ID = "profitsms";

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

    public function verify_sms($return_code, $number)
    {
        $response = $this->requester->get('http://profitsms.pl/check.php', [
            'apiKey' => $this->api,
            'code'   => $return_code,
            'smsNr'  => $number,
        ]);

        if ($response === false) {
            return SupportSms::NO_CONNECTION;
        }

        $content = $response->getBody();
        $raport = explode('|', $content);

        switch ($raport['0']) {
            case 1:
                return SupportSms::OK;

            case 0:
                return SupportSms::BAD_CODE;
        }

        return SupportSms::ERROR;
    }

    public function getSmsCode()
    {
        return $this->sms_code;
    }
}