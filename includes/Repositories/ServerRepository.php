<?php
namespace App\Repositories;

use App\Models\Server;
use App\Support\Database;
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
            "SELECT *, UNIX_TIMESTAMP(`last_active_at`) AS `last_active_at` FROM `ss_servers`"
        );

        return collect($statement)
            ->map(function (array $row) {
                return $this->mapToModel($row);
            })
            ->all();
    }

    public function get($id)
    {
        if ($id) {
            $statement = $this->db->statement(
                "SELECT *, UNIX_TIMESTAMP(`last_active_at`) FROM `ss_servers` WHERE `id` = ?"
            );
            $statement->execute([$id]);

            if ($data = $statement->fetch()) {
                return $this->mapToModel($data);
            }
        }

        return null;
    }

    public function findByToken($token)
    {
        $statement = $this->db->statement(
            "SELECT *, UNIX_TIMESTAMP(`last_active_at`) FROM `ss_servers` WHERE `token` = ?"
        );
        $statement->execute([$token]);
        $data = $statement->fetch();

        return $data ? $this->mapToModel($data) : null;
    }

    public function create($name, $ip, $port, $smsPlatformId, $transferPlatformId)
    {
        $token = $this->generateToken();

        $this->db
            ->statement(
                "INSERT INTO `ss_servers` " .
                    "SET `name` = ?, `ip` = ?, `port` = ?, `sms_platform` = ?, `transfer_platform` = ?, `token` = ?"
            )
            ->execute([$name, $ip, $port, $smsPlatformId, $transferPlatformId, $token]);

        return $this->get($this->db->lastId());
    }

    public function update($id, $name, $ip, $port, $smsPlatformId, $transferPlatformId)
    {
        $this->db
            ->statement(
                "UPDATE `ss_servers` " .
                    "SET `name` = ?, `ip` = ?, `port` = ?, `sms_platform` = ?, `transfer_platform` = ? " .
                    "WHERE `id` = ?"
            )
            ->execute([$name, $ip, $port, $smsPlatformId, $transferPlatformId, $id]);
    }

    public function delete($id)
    {
        $statement = $this->db->statement("DELETE FROM `ss_servers` WHERE `id` = ?");
        $statement->execute([$id]);

        return !!$statement->rowCount();
    }

    public function findByIpPort($ip, $port)
    {
        if (!$ip || !$port) {
            return null;
        }

        $statement = $this->db->statement(
            "SELECT *, UNIX_TIMESTAMP(`last_active_at`) AS `last_active_at` FROM `ss_servers` WHERE `ip` = ? AND `port` = ? LIMIT 1"
        );
        $statement->execute([$ip, $port]);
        $row = $statement->fetch();

        return $row ? $this->mapToModel($row) : null;
    }

    public function touch($id, $type, $version)
    {
        $this->db
            ->statement(
                "UPDATE `ss_servers` SET `type` = ?, `version` = ?, `last_active_at` = NOW() WHERE `id` = ?"
            )
            ->execute([$type, $version, $id]);
    }

    /**
     * @param int $id
     * @return string
     */
    public function regenerateToken($id)
    {
        $token = $this->generateToken();

        $this->db
            ->statement("UPDATE `ss_servers` SET `token` = ? WHERE `id` = ?")
            ->execute([$token, $id]);

        return $token;
    }

    private function mapToModel(array $data)
    {
        return new Server(
            as_int($data['id']),
            $data['name'],
            $data['ip'],
            $data['port'],
            as_int($data['sms_platform']),
            as_int($data['transfer_platform']),
            $data['type'],
            $data['version'],
            convert_date($data['last_active_at']),
            $data['token']
        );
    }

    /**
     * @return string
     */
    private function generateToken()
    {
        return substr(hash("sha256", uniqid()), 0, 32);
    }
}
