<?php
namespace App\Verification\Abstracts;

use App\Loggers\DatabaseLogger;
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
    protected DatabaseLogger $databaseLogger;
    protected FileLogger $fileLogger;

    public function __construct(
        DatabaseLogger $databaseLogger,
        FileLogger $fileLogger,
        PaymentPlatform $paymentPlatform,
        Requester $requester,
        UrlGenerator $url
    ) {
        $this->databaseLogger = $databaseLogger;
        $this->fileLogger = $fileLogger;
        $this->paymentPlatform = $paymentPlatform;
        $this->requester = $requester;
        $this->url = $url;
    }

    public static function getName(): string
    {
        return __(static::MODULE_ID);
    }

    public function getData(string $key): mixed
    {
        return array_get($this->paymentPlatform->getData(), $key);
    }

    /**
     * @return DataField[]
     */
    public static function getDataFields(): array
    {
        return [];
    }

    /**
     * @throws ProcessDataFieldsException
     */
    public static function processDataFields(array $data): array
    {
        return $data;
    }
}
