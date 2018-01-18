<?php

use App\PaymentModule;

$heart->register_payment_module("microsms", "PaymentModule_Microsms");

class PaymentModule_Microsms extends PaymentModule implements IPayment_Sms
{

    const SERVICE_ID = "microsms";

    /** @var  string */
    private $api;

    /** @var  string */
    private $service_id;

    /** @var  string */
    private $sms_code;

    function __construct()
    {
        parent::__construct();

        $this->api = $this->data['api'];
        $this->service_id = $this->data['service_id'];
        $this->sms_code = $this->data['sms_text'];
    }

    public function verify_sms($return_code, $number)
    {
        $handle = fopen(
            'http://microsms.pl/api/check.php' .
            '?userid=' . urlencode($this->api) .
            '&number=' . urlencode($number) .
            '&code=' . urlencode($return_code) .
            '&serviceid=' . urlencode($this->service_id),
            'r'
        );

        if ($handle) {
            $check = fgetcsv($handle, 1024);
            fclose($handle);

            if ($check[0] != 'E') {
                if ($check[0] == 1) {
                    return IPayment_Sms::OK;
                }

                return IPayment_Sms::BAD_CODE;
            }

            log_to_file(ERROR_LOG, "Microsms details: " . $check);

            return IPayment_Sms::MISCONFIGURATION;
        }

        return IPayment_Sms::NO_CONNECTION;
    }

    public function getSmsCode()
    {
        return $this->sms_code;
    }

}