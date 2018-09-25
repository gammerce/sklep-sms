<?php
namespace App;

use App\Models\Tariff;
use App\Requesting\Requester;

abstract class PaymentModule
{
    const SERVICE_ID = '';

    /** @var Database */
    protected $db;

    /** @var Requester */
    protected $requester;

    /** @var  string */
    protected $name;

    /** @var  bool */
    protected $support_sms = false;

    /** @var  bool */
    protected $support_transfer = false;

    /**
     * Data from columns: data & data_hidden
     *
     * @var array
     */
    protected $data = [];

    /** @var Tariff[] */
    protected $tariffs = [];

    public function __construct()
    {
        $this->db = app()->make(Database::class);
        $this->requester = app()->make(Requester::class);

        $result = $this->db->query($this->db->prepare(
            "SELECT `name`, `data`, `data_hidden`, `sms`, `transfer` " .
            "FROM `" . TABLE_PREFIX . "transaction_services` " .
            "WHERE `id` = '%s' ",
            [$this::SERVICE_ID]
        ));

        if (!$this->db->num_rows($result)) {
            output_page("An error occured in class: " . get_class($this) . " constructor. There is no " . $this::SERVICE_ID . " payment service in database.");
        }

        $row = $this->db->fetch_array_assoc($result);

        $this->name = $row['name'];
        $this->support_sms = (bool)$row['sms'];
        $this->support_transfer = (bool)$row['transfer'];

        $data = (array)json_decode($row['data'], true);
        foreach ($data as $key => $value) {
            $this->data[$key] = $value;
        }

        $data_hidden = (array)json_decode($row['data_hidden'], true);
        foreach ($data_hidden as $key => $value) {
            $this->data[$key] = $value;
        }

        // Pozyskujemy taryfy
        $result = $this->db->query($this->db->prepare(
            "SELECT t.id, t.provision, t.predefined, sn.number " .
            "FROM `" . TABLE_PREFIX . "tariffs` AS t " .
            "LEFT JOIN `" . TABLE_PREFIX . "sms_numbers` AS sn ON t.id = sn.tariff " .
            "WHERE sn.service = '%s' ",
            [$this::SERVICE_ID]
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
        return $this->support_transfer;
    }

    /**
     * @return boolean
     */
    public function supportSms()
    {
        return $this->support_sms;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param int $tariff_id
     *
     * @return Tariff
     */
    public function getTariffById($tariff_id)
    {
        return if_isset($this->tariffs[$tariff_id], null);
    }

    /**
     * @param string $number
     *
     * @return Tariff
     */
    public function getTariffByNumber($number)
    {
        return if_isset($this->tariffs[$number], null);
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
