<?php
namespace App\Verification\Abstracts;

use App\Database;
use App\Models\Tariff;
use App\Requesting\Requester;
use App\TranslationManager;
use App\Translator;

abstract class PaymentModule
{
    /** @var Database */
    protected $db;

    /** @var Requester */
    protected $requester;

    /** @var Translator */
    protected $langShop;

    /** @var string */
    protected $id;

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

    public function __construct(Database $database, Requester $requester, TranslationManager $translationManager)
    {
        $this->db = $database;
        $this->requester = $requester;
        $this->langShop = $translationManager->shop();

        $result = $this->db->query($this->db->prepare(
            "SELECT `name`, `data`, `data_hidden`, `sms`, `transfer` " .
            "FROM `" . TABLE_PREFIX . "transaction_services` " .
            "WHERE `id` = '%s' ",
            [$this->id]
        ));

        if (!$this->db->num_rows($result)) {
            // TODO Output should not happen here
            output_page("An error occured in class: " . get_class($this) . " constructor. There is no " . $this->id . " payment service in database.");
        }

        $row = $this->db->fetch_array_assoc($result);

        $this->name = $row['name'];

        $data = (array)json_decode($row['data'], true);
        foreach ($data as $key => $value) {
            $this->data[$key] = $value;
        }

        $dataHidden = (array)json_decode($row['data_hidden'], true);
        foreach ($dataHidden as $key => $value) {
            $this->data[$key] = $value;
        }

        // Pozyskujemy taryfy
        $result = $this->db->query($this->db->prepare(
            "SELECT t.id, t.provision, t.predefined, sn.number " .
            "FROM `" . TABLE_PREFIX . "tariffs` AS t " .
            "LEFT JOIN `" . TABLE_PREFIX . "sms_numbers` AS sn ON t.id = sn.tariff " .
            "WHERE sn.service = '%s' ",
            [$this->id]
        ));

        while ($row = $this->db->fetch_array_assoc($result)) {
            $tariff = new Tariff($row['id'], $row['provision'], $row['predefined'], $row['number']);

            $this->tariffs[$tariff->getId()] = $tariff;

            if ($tariff->getNumber() !== null) {
                $this->tariffs[$tariff->getNumber()] = $tariff;
            }
        }
    }

    /**
     * @return boolean
     */
    public function supportTransfer()
    {
        return $this instanceof SupportTransfer;
    }

    /**
     * @return boolean
     */
    public function supportSms()
    {
        return $this instanceof SupportSms;
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
            if ($tariff->getSmsCostBrutto() == $cost) {
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
}
