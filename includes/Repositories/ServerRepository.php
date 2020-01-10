<?php
namespace App\Repositories;

use App\Models\Server;
use App\System\Database;
use App\System\Settings;

class ServerRepository
{
    /** @var Database */
    private $db;

    /** @var Settings */
    private $settings;

    public function __construct(Database $db, Settings $settings)
    {
        $this->db = $db;
        $this->settings = $settings;
    }

    /**
     * @return Server[]
     */
    public function all()
    {
        $statement = $this->db->query(
            "SELECT *, UNIX_TIMESTAMP(`last_active_at`) AS `last_active_at` FROM `" .
                TABLE_PREFIX .
                "servers`"
        );

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
                "SELECT *, UNIX_TIMESTAMP(`last_active_at`) FROM `" .
                    TABLE_PREFIX .
                    "servers` WHERE `id` = ?"
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

    public function findByIpPort($ip, $port)
    {
        if (!$ip || !$port) {
            return null;
        }

        $statement = $this->db->statement(
            "SELECT *, UNIX_TIMESTAMP(`last_active_at`) AS `last_active_at` FROM `" .
                TABLE_PREFIX .
                "servers` WHERE `ip` = ? AND `port` = ? LIMIT 1"
        );
        $statement->execute([$ip, $port]);
        $row = $statement->fetch();
        return $row ? $this->mapToModel($row) : null;
    }

    public function touch($id, $type, $version)
    {
        $this->db
            ->statement(
                "UPDATE `" .
                    TABLE_PREFIX .
                    "servers` SET `type` = ?, `version` = ?, `last_active_at` = NOW() WHERE `id` = ?"
            )
            ->execute([$type, $version, $id]);
    }

    private function mapToModel(array $data)
    {
        return new Server(
            (int) $data['id'],
            $data['name'],
            $data['ip'],
            $data['port'],
            $data['sms_platform'],
            $data['type'],
            $data['version'],
            $data['last_active_at']
                ? date($this->settings->getDateFormat(), $data['last_active_at'])
                : null
        );
    }
}
