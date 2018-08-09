<?php

use App\PaymentModule;

class PaymentModule_Mintshost extends PaymentModule implements IPayment_Sms
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
        $status = $this->requester->get('https://mintshost.pl/sms2.php', [
            'kod'   => $return_code,
            'sms'   => $number,
            'email' => $this->email,
        ]);

        if ($status === false) {
            return IPayment_Sms::NO_CONNECTION;
        }

        if ($status === "1") {
            return IPayment_Sms::OK;
        }

        if ($status === "0") {
            return IPayment_Sms::BAD_CODE;
        }

        if ($status === "2") {
            return IPayment_Sms::BAD_EMAIL;
        }

        if ($status === "3") {
            return IPayment_Sms::BAD_DATA;
        }

        return IPayment_Sms::ERROR;
    }

    public function getSmsCode()
    {
        return $this->sms_code;
    }
}
