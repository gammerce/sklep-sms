<?php

use App\PaymentModule;

class PaymentModule_Simpay extends PaymentModule implements IPayment_Sms
{
    const SERVICE_ID = "simpay";

    /** @var string */
    private $key;

    /** @var string */
    private $secret;

    /** @var string */
    private $service_id;

    /** @var string */
    private $sms_code;

    public function __construct()
    {
        parent::__construct();

        $this->key = $this->data['key'];
        $this->secret = $this->data['secret'];
        $this->service_id = $this->data['service_id'];
        $this->sms_code = $this->data['sms_text'];
    }

    public function verify_sms($sms_code, $sms_number)
    {
        $response = curl_get_contents('https://simpay.pl/api/1/status', 10, true, [
            'auth'       => [
                'key'    => $this->key,
                'secret' => $this->secret,
            ],
            'service_id' => $this->service_id,
            'number'     => $sms_number,
            'code'       => $sms_code,
        ]);

        if (!strlen($response)) {
            return IPayment_Sms::NO_CONNECTION;
        }

        $response = json_decode($response, true);

        if (isset($response['respond']['status']) && $response['respond']['status'] == 'OK') {
            return [
                'status' => IPayment_Sms::OK,
                'free'   => $response['respond']['test'],
            ];
        }

        if (isset($response['error'][0]) && is_array($response['error'][0])) {
            switch (intval($response['error'][0]['error_code'])) {
                case 103:
                case 104:
                    return IPayment_Sms::BAD_API;

                case 404:
                case 405:
                    return IPayment_Sms::BAD_CODE;
            }

            return [
                'status' => IPayment_Sms::UNKNOWN,
                'text'   => $response['error'][0]['error_name'],
            ];
        }

        return IPayment_Sms::ERROR;
    }

    public function getSmsCode()
    {
        return $this->sms_code;
    }

}