<?php
namespace App\View\Html;

class PaymentRef extends Link
{
    public function __construct($id, $type)
    {
        parent::__construct("$type ($id)", url("/admin/payment_{$type}", ["record" => $id]));
    }
}
