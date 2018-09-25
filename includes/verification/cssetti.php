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
        $data = $response->json();

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

        $content = $response->getBody();

        if (!is_numeric($content)) {
            return IPayment_Sms::ERROR;
        }

        $content = strval(floatval($content));

        if ($content == '0') {
            return IPayment_Sms::BAD_CODE;
        }

        if ($content == '-1') {
            return IPayment_Sms::BAD_API;
        }

        if ($content == '-2' || $content == '-3') {
            return IPayment_Sms::SERVER_ERROR;
        }

        if (floatval($content) > 0) {
            if (!isset($this->numbers[$content])) {
                return [
                    'status' => IPayment_Sms::BAD_NUMBER,
                    'tariff' => null,
                ];
            }

            if ($this->numbers[$content] != $number) {
                return [
                    'status' => IPayment_Sms::BAD_NUMBER,
                    'tariff' => $this->getTariffByNumber($this->numbers[$content])->getId(),
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
