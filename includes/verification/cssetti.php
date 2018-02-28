<?php

use App\PaymentModule;

class PaymentModule_Cssetti extends PaymentModule implements IPayment_Sms
{
    const SERVICE_ID = "cssetti";

    /** @var  string */
    private $account_id;

    /** @var  string */
    private $sms_code;

    /** @var array */
    private $numbers = [];

    public function __construct()
    {
        parent::__construct();

        $response = $this->requester->get('http://cssetti.pl/Api/SmsApiV2GetData.php');
        $data = json_decode($response, true);

        // CSSetti dostarcza w feedzie kod sms
        $this->sms_code = $data['Code'];

        foreach ($data['Numbers'] as $number_data) {
            $this->numbers[strval(floatval($number_data['TopUpAmount']))] = strval($number_data['Number']);
        }

        $this->account_id = $this->data['account_id'];
    }

    public function verify_sms($return_code, $number)
    {
        $response = $this->requester->get('http://cssetti.pl/Api/SmsApiV2CheckCode.php', [
            'UserId' => $this->account_id,
            'Code'   => $return_code,
        ]);

        if ($response === false) {
            return IPayment_Sms::NO_CONNECTION;
        }

        if (!is_numeric($response)) {
            return IPayment_Sms::ERROR;
        }

        $response = strval(floatval($response));

        if ($response == '0') {
            return IPayment_Sms::BAD_CODE;
        }

        if ($response == '-1') {
            return IPayment_Sms::BAD_API;
        }

        if ($response == '-2' || $response == '-3') {
            return IPayment_Sms::SERVER_ERROR;
        }

        if (floatval($response) > 0) {
            if (!isset($this->numbers[$response]) || $this->numbers[$response] != $number) {
                return [
                    'status' => IPayment_Sms::BAD_NUMBER,
                    'tariff' => $this->getTariffByNumber($this->numbers[$response])->getId(),
                ];
            }

            return IPayment_Sms::OK;
        }

        return IPayment_Sms::ERROR;
    }

    public function getSmsCode()
    {
        return $this->sms_code;
    }
}
