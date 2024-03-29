<?php
namespace App\Repositories;

use App\Models\Server;
use App\Server\Platform;
use App\Support\Database;
use App\System\Settings;

class ServerRepository
{
    private Database $db;
    private Settings $settings;

    public function __construct(Database $db, Settings $settings)
    {
        $this->db = $db;
        $this->settings = $settings;
    }

    /**
     * @return Server[]
     */
    public function all(): array
    {
        $statement = $this->db->query(
            "SELECT *, UNIX_TIMESTAMP(`last_active_at`) AS `last_active_at` FROM `ss_servers`"
        );

        return collect($statement)
            ->map(fn(array $row) => $this->mapToModel($row))
            ->all();
    }

    public function get($id): ?Server
    {
        if ($id) {
            $statement = $this->db->statement(
                "SELECT *, UNIX_TIMESTAMP(`last_active_at`) FROM `ss_servers` WHERE `id` = ?"
            );
            $statement->bindAndExecute([$id]);

            if ($data = $statement->fetch()) {
                return $this->mapToModel($data);
            }
        }

        return null;
    }

    public function findByToken($token): ?Server
    {
        $statement = $this->db->statement(
            "SELECT *, UNIX_TIMESTAMP(`last_active_at`) FROM `ss_servers` WHERE `token` = ?"
        );
        $statement->bindAndExecute([$token]);
        $data = $statement->fetch();

        return $data ? $this->mapToModel($data) : null;
    }

    public function create($name, $ip, $port, $smsPlatformId, array $transferPlatformIds): Server
    {
        $token = $this->generateToken();

        $this->db
            ->statement(
                "INSERT INTO `ss_servers` " .
                    "SET `name` = ?, `ip` = ?, `port` = ?, `sms_platform` = ?, `transfer_platform` = ?, `token` = ?"
            )
            ->bindAndExecute([
                $name,
                $ip,
                $port,
                $smsPlatformId,
                implode(",", $transferPlatformIds),
                $token,
            ]);

        return $this->get($this->db->lastId());
    }

    public function update($id, $name, $ip, $port, $smsPlatformId, array $transferPlatformIds): void
    {
        $this->db
            ->statement(
                "UPDATE `ss_servers` " .
                    "SET `name` = ?, `ip` = ?, `port` = ?, `sms_platform` = ?, `transfer_platform` = ? " .
                    "WHERE `id` = ?"
            )
            ->bindAndExecute([
                $name,
                $ip,
                $port,
                $smsPlatformId,
                implode(",", $transferPlatformIds),
                $id,
            ]);
    }

    public function delete($id): bool
    {
        $statement = $this->db->statement("DELETE FROM `ss_servers` WHERE `id` = ?");
        $statement->bindAndExecute([$id]);

        return !!$statement->rowCount();
    }

    public function touch($id, Platform $type, $version): void
    {
        $this->db
            ->statement(
                "UPDATE `ss_servers` SET `type` = ?, `version` = ?, `last_active_at` = NOW() WHERE `id` = ?"
            )
            ->bindAndExecute([$type, $version, $id]);
    }

    /**
     * @param int $id
     * @return string
     */
    public function regenerateToken($id): string
    {
        $token = $this->generateToken();

        $this->db
            ->statement("UPDATE `ss_servers` SET `token` = ? WHERE `id` = ?")
            ->bindAndExecute([$token, $id]);

        return $token;
    }

    private function mapToModel(array $data): Server
    {
        return new Server(
            as_int($data["id"]),
            $data["name"],
            $data["ip"],
            $data["port"],
            as_int($data["sms_platform"]),
            explode_int_list($data["transfer_platform"], ","),
            $data["type"],
            $data["version"],
            as_datetime_string($data["last_active_at"]),
            $data["token"]
        );
    }

    private function generateToken(): string
    {
        return substr(hash("sha256", uniqid()), 0, 32);
    }
}
