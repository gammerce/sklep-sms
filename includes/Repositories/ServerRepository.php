<?php
namespace App\Repositories;

use App\Database;
use App\Models\Server;

class ServerRepository
{
    /** @var Database */
    protected $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create($name, $ip, $port, $smsService = '')
    {
        $this->db->query(
            $this->db->prepare(
                "INSERT INTO `" .
                    TABLE_PREFIX .
                    "servers` " .
                    "SET `name`='%s', `ip`='%s', `port`='%s', `sms_service`='%s'",
                [$name, $ip, $port, $smsService]
            )
        );

        $id = $this->db->lastId();

        return new Server($id, $name, $ip, $port, $smsService);
    }
}
