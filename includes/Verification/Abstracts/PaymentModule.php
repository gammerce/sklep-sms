<?php
namespace App\Verification\Abstracts;

use App\Models\PaymentPlatform;
use App\Models\Tariff;
use App\Requesting\Requester;
use App\System\Database;
use App\Verification\DataField;

abstract class PaymentModule
{
    const MODULE_ID = '';

    /** @var Requester */
    protected $requester;

    /** @var Database */
    private $db;

    /** @var PaymentPlatform */
    private $paymentPlatform;

    /** @var Tariff[] */
    private $tariffs = [];

    /** @var bool */
    private $areTariffsFetched = false;

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
    public function getDataFields()
    {
        return [];
    }

    /**
     * @return Tariff[]
     */
    public function getTariffs()
    {
        if (!$this->areTariffsFetched) {
            $this->fetchTariffs();
        }

        return array_unique($this->tariffs);
    }

    /**
     * @param int $tariffId
     *
     * @return Tariff|null
     */
    public function getTariffById($tariffId)
    {
        return array_get($this->getTariffs(), $tariffId);
    }

    /**
     * @param string $number
     *
     * @return Tariff|null
     */
    public function getTariffByNumber($number)
    {
        return array_get($this->getTariffs(), $number);
    }

    /**
     * Returns tariff by sms cost gross
     *
     * @param float $cost
     *
     * @return Tariff|null
     */
    public function getTariffBySmsCostGross($cost)
    {
        foreach ($this->getTariffs() as $tariff) {
            if ($tariff->getSmsCostGross() == $cost) {
                return $tariff;
            }
        }

        return null;
    }

    public function getModuleId()
    {
        return $this::MODULE_ID;
    }

    private function fetchTariffs()
    {
        $result = $this->db->query(
            $this->db->prepare(
                "SELECT t.id, t.provision, t.predefined, sn.number " .
                    "FROM `" .
                    TABLE_PREFIX .
                    "tariffs` AS t " .
                    "LEFT JOIN `" .
                    TABLE_PREFIX .
                    "sms_numbers` AS sn ON t.id = sn.tariff " .
                    "WHERE sn.service = '%s' ",
                [$this->getModuleId()]
            )
        );

        while ($row = $this->db->fetchArrayAssoc($result)) {
            $tariff = new Tariff($row['id'], $row['provision'], $row['predefined'], $row['number']);

            $this->tariffs[$tariff->getId()] = $tariff;

            if ($tariff->getNumber() !== null) {
                $this->tariffs[$tariff->getNumber()] = $tariff;
            }
        }

        $this->areTariffsFetched = true;
    }
}
