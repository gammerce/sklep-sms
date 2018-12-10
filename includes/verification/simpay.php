<?php

use App\PaymentModule;

class PaymentModule_Simpay extends PaymentModule implements SupportSms
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
        $response = $this->requester->post('https://simpay.pl/api/1/status', [
            'params' => [
                'auth'       => [
                    'key'    => $this->key,
                    'secret' => $this->secret,
                ],
                'service_id' => $this->service_id,
                'number'     => $sms_number,
                'code'       => $sms_code,
            ],
        ]);

        if (!$response) {
            return SupportSms::NO_CONNECTION;
        }

        $content = $response->json();

        if (isset($content['respond']['status']) && $content['respond']['status'] == 'OK') {
            return [
                'status' => SupportSms::OK,
                'free'   => $content['respond']['test'],
            ];
        }

        if (isset($content['error'][0]) && is_array($content['error'][0])) {
            switch (intval($content['error'][0]['error_code'])) {
                case 103:
                case 104:
                    return SupportSms::BAD_API;

                case 404:
                case 405:
                    return SupportSms::BAD_CODE;
            }

            return [
                'status' => SupportSms::UNKNOWN,
                'text'   => $content['error'][0]['error_name'],
            ];
        }

        return SupportSms::ERROR;
    }

    public function getSmsCode()
    {
        return $this->sms_code;
    }

}