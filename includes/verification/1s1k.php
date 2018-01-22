<?php

use App\PaymentModule;

$heart->register_payment_module("1s1k", "PaymentModule_1s1k");

class PaymentModule_1s1k extends PaymentModule implements IPayment_Sms
{
    const SERVICE_ID = "1s1k";

    /** @var string */
    private $api;

    /** @var string */
    private $sms_code;

    private $rates = [];

    public function __construct()
    {
        parent::__construct();

        $this->api = $this->data['api'];
        $this->sms_code = $this->data['sms_text'];

        $this->rates = [
            '0.65'  => '7136',
            '1.30'  => '7255',
            '1.95'  => '7355',
            '2.60'  => '7455',
            '3.25'  => '7555',
            '3.90'  => '7636',
            '4.55'  => '77464',
            '5.20'  => '78464',
            '5.85'  => '7936',
            '6.50'  => '91055',
            '7.15'  => '91155',
            '9.10'  => '91455',
            '10.40' => '91664',
            '12.35' => '91955',
            '13.00' => '92055',
            '16.25' => '92555',
        ];
    }

    public function verify_sms($return_code, $number)
    {
        $content = curl_get_contents(
            'http://www.1shot1kill.pl/api' .
            '?type=sms' .
            '&key=' . urlencode($this->api) .
            '&sms_code=' . urlencode($return_code) .
            '&comment='
        );

        if ($content === false) {
            return IPayment_Sms::NO_CONNECTION;
        }

        $response = json_decode($content, true);
        if (!is_array($response)) {
            return IPayment_Sms::BAD_API;
        }

        $response_number = $this->rates[number_format(floatval($response['amount']), 2)];

        switch ($response['status']) {
            case 'ok':
                if ($response_number == $number) {
                    return IPayment_Sms::OK;
                }

                return [
                    'status' => IPayment_Sms::BAD_NUMBER,
                    'tariff' => $this->getTariffByNumber($response_number)->getId(),
                ];

            case 'fail':
                return IPayment_Sms::BAD_CODE;

            case 'error':
                switch ($response['desc']) {
                    case 'internal api error':
                        return IPayment_Sms::SERVER_ERROR;

                    case 'wrong api type':
                    case 'wrong api key':
                        return IPayment_Sms::BAD_API;
                }

                return [
                    'status' => IPayment_Sms::UNKNOWN,
                    'text'   => $response['desc'],
                ];
        }

        return IPayment_Sms::ERROR;
    }

    public function getSmsCode()
    {
        return $this->sms_code;
    }
}