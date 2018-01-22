<?php

use App\PaymentModule;

$heart->register_payment_module("homepay", "PaymentModuleHomepay");

class PaymentModuleHomepay extends PaymentModule implements IPayment_Sms
{
    const SERVICE_ID = "homepay";

    /** @var  string */
    private $api;

    /** @var  string */
    private $sms_code;

    public function __construct()
    {
        parent::__construct();

        $this->sms_code = $this->data['sms_text'];
        $this->api = $this->data['api'];
    }

    public function verify_sms($return_code, $number)
    {
        $handle = fopen(
            'http://homepay.pl/API/check_code.php' .
            '?usr_id=' . urlencode($this->api) .
            '&acc_id=' . urlencode($this->data[$number]) .
            '&code=' . urlencode($return_code),
            'r'
        );

        if ($handle) {
            $status = fgets($handle, 8);
            fclose($handle);

            if ($status == '0') {
                return IPayment_Sms::BAD_CODE;
            }

            if ($status == '1') {
                return IPayment_Sms::OK;
            }

            return IPayment_Sms::SERVER_ERROR;
        }

        return IPayment_Sms::NO_CONNECTION;
    }

    public function getSmsCode()
    {
        return $this->sms_code;
    }

}