<?php
namespace App\Verification\Abstracts;

use App\Models\PaymentPlatform;
use App\Requesting\Requester;
use App\System\Database;
use App\Verification\DataField;

abstract class PaymentModule
{
    const MODULE_ID = '';

    /** @var Database */
    private $db;

    /** @var Requester */
    protected $requester;

    /** @var PaymentPlatform */
    protected $paymentPlatform;

    public function __construct(
        Database $database,
        Requester $requester,
        PaymentPlatform $paymentPlatform
    ) {
        $this->db = $database;
        $this->requester = $requester;
        $this->paymentPlatform = $paymentPlatform;
    }

    /**
     * @param mixed $key
     * @return mixed
     */
    public function getData($key = null)
    {
        $data = $this->paymentPlatform->getData();

        return $key ? array_get($data, $key) : $data;
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
