<?php
namespace App\View\Html;

use App\Payment\General\PaymentMethod;

class PaymentRef extends Link
{
    public function __construct($id, PaymentMethod $paymentMethod)
    {
        parent::__construct(
            "$paymentMethod ($id)",
            url("/admin/payments", ["method" => (string) $paymentMethod, "record" => $id])
        );
    }
}
