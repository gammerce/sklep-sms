<?php
namespace App\Entity;

use App\Database;

class Pricelist
{
    /** @var int */
    private $id;

    /** @var string */
    private $service;

    /** @var int */
    private $tariff;

    /** @var int */
    private $amount;

    /** @var int */
    private $server;

    public function __construct($id, $service, $tariff, $amount, $server)
    {
        $this->id = $id;
        $this->service = $service;
        $this->tariff = $tariff;
        $this->amount = $amount;
        $this->server = $server;
    }

    public static function create($service, $tariff, $amount, $server)
    {
        /** @var Database $db */
        $db = app()->make(Database::class);

        $db->query($db->prepare(
            "INSERT INTO `" . TABLE_PREFIX . "pricelist` (`service`, `tariff`, `amount`, `server`) " .
            "VALUES( '%s', '%d', '%d', '%d' )",
            [$service, $tariff, $amount, $server]
        ));

        $id = $db->last_id();

        return new Pricelist($id, $service, $tariff, $amount, $server);
    }
}