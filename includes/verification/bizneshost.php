<?php

use App\PaymentModule;

class PaymentModule_Bizneshost extends PaymentModule implements SupportSms
{
    const SERVICE_ID = 'bizneshost';

    /** @var  int */
    protected $uid;

    /** @var  string */
    protected $sms_code;

    public function __construct()
    {
        parent::__construct();

        $this->sms_code = $this->data['sms_text'];
        $this->uid = $this->data['uid'];
    }

    public function verify_sms($return_code, $number)
    {
        if (!strlen($this->uid)) {
            return SupportSms::BAD_DATA;
        }

        $response = $this->requester->get('http://biznes-host.pl/api/sprawdzkod_v2.php', [
            'uid' => $this->uid,
            'kod' => $return_code,
        ]);

        if ($response === false) {
            return SupportSms::NO_CONNECTION;
        }

        $status = $response->getBody();
        $status_exploded = explode(':', $status);

        // Bad code
        if ($status_exploded[0] == 'E') {
            return SupportSms::BAD_CODE;
        }

        // Code is correct
        if ($status_exploded[0] == '1') {
            // Check whether prices are equal
            if (abs(get_sms_cost_brutto($number) / 100 - floatval($status_exploded[1])) < 0.1) {
                return SupportSms::OK;
            }

            $tariff = $this->getTariffBySmsCostBrutto($status_exploded[1]);

            return [
                'status' => SupportSms::BAD_NUMBER,
                'tariff' => !is_null($tariff) ? $tariff->getId() : null,
            ];
        }

        // Code used
        if ($status_exploded[0] == '2') {
            return SupportSms::BAD_CODE;
        }

        // No code - $return_code is empty
        if ($status_exploded[0] == '-1') {
            return SupportSms::BAD_CODE;
        }

        // No uid
        if ($status_exploded[0] == '-2') {
            return SupportSms::BAD_DATA;
        }

        return SupportSms::ERROR;
    }

    public function getSmsCode()
    {
        return $this->sms_code;
    }

}