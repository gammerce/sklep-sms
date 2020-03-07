<?php
namespace App\Verification\Abstracts;

use App\Models\PaymentPlatform;
use App\Requesting\Requester;
use App\Verification\DataField;

abstract class PaymentModule
{
    const MODULE_ID = '';

    /** @var Requester */
    protected $requester;

    /** @var PaymentPlatform */
    protected $paymentPlatform;

    public function __construct(Requester $requester, PaymentPlatform $paymentPlatform)
    {
        $this->requester = $requester;
        $this->paymentPlatform = $paymentPlatform;
    }

    /**
     * @param mixed $key
     * @return mixed
     */
    public function getData($key)
    {
        return array_get($this->paymentPlatform->getData(), $key);
    }

    /**
     * @return DataField[]
     */
    public static function getDataFields()
    {
        return [];
    }

    public function getModuleId()
    {
        return $this::MODULE_ID;
    }
}
