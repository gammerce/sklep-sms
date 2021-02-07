<?php
namespace App\Payment\General;

final class PaymentResult
{
    private PaymentResultType $type;

    /** @var mixed */
    private $data;

    public function __construct(PaymentResultType $type, $data)
    {
        $this->type = $type;
        $this->data = $data;
    }

    public function getType(): PaymentResultType
    {
        return $this->type;
    }

    public function getData()
    {
        return $this->data;
    }
}
