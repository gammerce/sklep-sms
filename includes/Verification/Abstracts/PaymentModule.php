<?php
namespace App\Verification\Abstracts;

use App\Models\PaymentPlatform;
use App\Requesting\Requester;
use App\Routing\UrlGenerator;
use App\Verification\DataField;

abstract class PaymentModule
{
    const MODULE_ID = '';

    /** @var Requester */
    protected $requester;

    /** @var PaymentPlatform */
    protected $paymentPlatform;

    /** @var UrlGenerator */
    protected $url;

    public function __construct(Requester $requester, PaymentPlatform $paymentPlatform, UrlGenerator $url)
    {
        $this->requester = $requester;
        $this->paymentPlatform = $paymentPlatform;
        $this->url = $url;
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
