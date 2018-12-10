<?php

use App\PaymentModule;

class PaymentModuleHomepay extends PaymentModule implements SupportSms
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

    public function verifySms($returnCode, $number)
    {
        $handle = fopen(
            'http://homepay.pl/API/check_code.php' .
            '?usr_id=' . urlencode($this->api) .
            '&acc_id=' . urlencode($this->data[$number]) .
            '&code=' . urlencode($returnCode),
            'r'
        );

        if ($handle) {
            $status = fgets($handle, 8);
            fclose($handle);

            if ($status == '0') {
                return SupportSms::BAD_CODE;
            }

            if ($status == '1') {
                return SupportSms::OK;
            }

            return SupportSms::SERVER_ERROR;
        }

        return SupportSms::NO_CONNECTION;
    }

    public function getSmsCode()
    {
        return $this->sms_code;
    }

}