<?php

$heart->register_payment_module("intersms", "PaymentModuleIntersms");

class PaymentModuleIntersms extends PaymentModule implements IPayment_Sms
{

    const SERVICE_ID = "intersms";

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
    }

    public function getSmsCode()
    {
        return $this->sms_code;
    }

}