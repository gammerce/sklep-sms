<?php
namespace App\Verification\Abstracts;

use App\Loggers\FileLogger;
use App\Models\PaymentPlatform;
use App\Requesting\Requester;
use App\Routing\UrlGenerator;
use App\Verification\DataField;
use App\Verification\Exceptions\ProcessDataFieldsException;

abstract class PaymentModule
{
    const MODULE_ID = "";

    protected Requester $requester;
    protected PaymentPlatform $paymentPlatform;
    protected UrlGenerator $url;
    protected FileLogger $fileLogger;

    public function __construct(
        Requester $requester,
        PaymentPlatform $paymentPlatform,
        UrlGenerator $url,
        FileLogger $fileLogger
    ) {
        $this->requester = $requester;
        $this->paymentPlatform = $paymentPlatform;
        $this->url = $url;
        $this->fileLogger = $fileLogger;
    }

    public static function getName(): string
    {
        return __(static::MODULE_ID);
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

    /**
     * @param array $data
     * @return array
     * @throws ProcessDataFieldsException
     */
    public static function processDataFields(array $data)
    {
        return $data;
    }
}
