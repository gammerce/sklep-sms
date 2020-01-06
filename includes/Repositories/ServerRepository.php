<?php
namespace App\Repositories;

use App\Models\Server;
use App\System\Database;

class ServerRepository
{
    /** @var Database */
    protected $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * @return Server[]
     */
    public function all()
    {
        $statement = $this->db->query("SELECT * FROM `" . TABLE_PREFIX . "servers`");

        $servers = [];
        foreach ($statement as $row) {
            $servers[] = $this->mapToModel($row);
        }

        return $servers;
    }

    public function get($id)
    {
        if ($id) {
            $statement = $this->db->statement(
                "SELECT * FROM `" . TABLE_PREFIX . "servers` WHERE `id` = ?"
            );
            $statement->execute([$id]);

            if ($data = $statement->fetch()) {
                return $this->mapToModel($data);
            }
        }

        return null;
    }

    public function create($name, $ip, $port, $smsPlatformId = null)
    {
        $this->db
            ->statement(
                "INSERT INTO `" .
                    TABLE_PREFIX .
                    "servers` " .
                    "SET `name` = ?, `ip` = ?, `port` = ?, `sms_platform` = ?"
            )
            ->execute([$name, $ip, $port, $smsPlatformId]);

        return $this->get($this->db->lastId());
    }

    public function update($id, $name, $ip, $port, $smsPlatformId = null)
    {
        $this->db
            ->statement(
                "UPDATE `" .
                    TABLE_PREFIX .
                    "servers` " .
                    "SET `name` = ?, `ip` = ?, `port` = ?, `sms_platform` = ? " .
                    "WHERE `id` = ?"
            )
            ->execute([$name, $ip, $port, $smsPlatformId, $id]);
    }

    public function delete($id)
    {
        $statement = $this->db->statement(
            "DELETE FROM `" . TABLE_PREFIX . "servers` WHERE `id` = ?"
        );
        $statement->execute([$id]);
        return !!$statement->rowCount();
    }

    private function mapToModel(array $data)
    {
        return new Server(
            intval($data['id']),
            $data['name'],
            $data['ip'],
            $data['port'],
            $data['sms_platform'],
            $data['type'],
            $data['version']
        );
    }
}
