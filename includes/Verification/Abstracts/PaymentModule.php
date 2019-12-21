<?php
namespace App\Verification\Abstracts;

use App\Exceptions\InvalidConfigException;
use App\Models\Tariff;
use App\Requesting\Requester;
use App\System\Database;
use App\Translation\TranslationManager;
use App\Translation\Translator;

abstract class PaymentModule
{
    const MODULE_ID = '';

    /** @var Database */
    protected $db;

    /** @var Requester */
    protected $requester;

    /** @var Translator */
    protected $langShop;

    /** @var  string */
    protected $name;

    /**
     * Data from columns: data & data_hidden
     *
     * @var array
     */
    protected $data = [];

    /** @var Tariff[] */
    protected $tariffs = [];

    public function __construct(
        Database $database,
        Requester $requester,
        TranslationManager $translationManager
    ) {
        $this->db = $database;
        $this->requester = $requester;
        $this->langShop = $translationManager->shop();

        // TODO Move heavy things out from constructor

        $result = $this->db->query(
            $this->db->prepare(
                "SELECT `name`, `data`, `data_hidden`, `sms`, `transfer` " .
                    "FROM `" .
                    TABLE_PREFIX .
                    "transaction_services` " .
                    "WHERE `id` = '%s' ",
                [$this->getModuleId()]
            )
        );

        if (!$this->db->numRows($result)) {
            $className = get_class($this);
            throw new InvalidConfigException(
                "An error occured in class: [$className] constructor. There is no [{$this->getModuleId()}] payment service in database."
            );
        }

        $row = $this->db->fetchArrayAssoc($result);

        $this->name = $row['name'];

        $data = (array) json_decode($row['data'], true);
        foreach ($data as $key => $value) {
            $this->data[$key] = $value;
        }

        $dataHidden = (array) json_decode($row['data_hidden'], true);
        foreach ($dataHidden as $key => $value) {
            $this->data[$key] = $value;
        }

        // Pozyskujemy taryfy
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
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param int $tariffId
     *
     * @return Tariff|null
     */
    public function getTariffById($tariffId)
    {
        return array_get($this->tariffs, $tariffId);
    }

    /**
     * @param string $number
     *
     * @return Tariff|null
     */
    public function getTariffByNumber($number)
    {
        return array_get($this->tariffs, $number);
    }

    /**
     * Returns tariff by sms cost brutto
     *
     * @param float $cost
     *
     * @return Tariff|null
     */
    public function getTariffBySmsCostBrutto($cost)
    {
        foreach ($this->tariffs as $tariff) {
            if ($tariff->getSmsCostGross() == $cost) {
                return $tariff;
            }
        }

        return null;
    }

    /**
     * @return Tariff[]
     */
    public function getTariffs()
    {
        return array_unique($this->tariffs);
    }

    public function getModuleId()
    {
        return $this::MODULE_ID;
    }
}
