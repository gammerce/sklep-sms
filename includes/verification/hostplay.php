<?php

$heart->register_payment_module("hostplay", "PaymentModuleHostplay");

class PaymentModuleHostplay extends PaymentModule implements IPayment_Sms
{

    const SERVICE_ID = "hostplay";

    /** @var  string */
    protected $userId;

    /** @var  string */
    protected $sms_code;

    function __construct()
    {
        parent::__construct();

        $this->sms_code = $this->data['sms_text'];
        $this->userId = $this->data['user_id'];
    }

    public function verify_sms($return_code, $number)
    {
        $response = curl_get_contents(
            'http://hostplay.pl/api/payment/api_code_verify.php' .
            '?payment=homepay_sms' .
            '&userid=' . urlencode($this->userId) .
            '&comment=SklepSMS' .
            '&code=' . urlencode($return_code)
        );

        file_put_contents('test.log', $response);

        $response = json_decode($response, true);

        if (strtoupper($response['status']) == 'OK') {
            // Check whether prices are equal
            if (abs(get_sms_cost_brutto($number) - intval($response['kwota'] * 100 * 2)) < 10) {
                return IPayment_Sms::OK;
            }

            $tariff = $this->getTariffBySmsCostBrutto($response['kwota'] * 2);
            return array(
                'status' => IPayment_Sms::BAD_NUMBER,
                'tariff' => !is_null($tariff) ? $tariff->getId() : NULL
            );
        }

        if (strtoupper($response['status']) == 'FAIL'/* && strtoupper($response['error']) == "BAD_CODE"*/) {
            return IPayment_Sms::BAD_CODE;
        }

        return IPayment_Sms::SERVER_ERROR;
    }

    public function getSmsCode()
    {
        return $this->sms_code;
    }

}