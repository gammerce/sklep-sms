<?php

use App\PaymentModule;

class PaymentModule_Mintshost extends PaymentModule implements SupportSms
{
    const SERVICE_ID = "mintshost";

    /** @var  string */
    private $email;

    /** @var  string */
    private $sms_code;

    public function __construct()
    {
        parent::__construct();

        $this->sms_code = $this->data['sms_text'];
        $this->email = $this->data['email'];
    }

    public function verify_sms($return_code, $number)
    {
        $response = $this->requester->get('https://mintshost.pl/sms2.php', [
            'kod'   => $return_code,
            'sms'   => $number,
            'email' => $this->email,
        ]);

        if ($response === false) {
            return SupportSms::NO_CONNECTION;
        }

        $status = $response->getBody();

        if ($status === "1") {
            return SupportSms::OK;
        }

        if ($status === "0") {
            return SupportSms::BAD_CODE;
        }

        if ($status === "2") {
            return SupportSms::BAD_EMAIL;
        }

        if ($status === "3") {
            return SupportSms::BAD_DATA;
        }

        return SupportSms::ERROR;
    }

    public function getSmsCode()
    {
        return $this->sms_code;
    }
}
