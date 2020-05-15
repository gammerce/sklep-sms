<?php
namespace App\Payment\General;

class PaymentResult
{
    /** @var PaymentResultType */
    private $type;

    /** @var mixed */
    private $data;

    public function __construct(PaymentResultType $type, $data)
    {
        $this->type = $type;
        $this->data = $data;
    }

    /**
     * @return PaymentResultType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
}
