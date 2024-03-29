<?php
namespace App\ServiceModules\ExtraFlags;

use App\Exceptions\EntityNotFoundException;
use App\Support\Database;

class PlayerFlagRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * @param array $data
     * @return PlayerFlag
     * @throws EntityNotFoundException
     */
    public function findOrFail(array $data): PlayerFlag
    {
        [$params, $values] = map_to_params($data, true);
        $params = implode(" AND ", $params);

        $statement = $this->db->statement(
            "SELECT * FROM `ss_players_flags` " . ($params ? "WHERE {$params}" : "")
        );
        $statement->bindAndExecute($values);

        $data = $statement->fetch();
        if (!$data) {
            throw new EntityNotFoundException();
        }

        return $this->mapToModel($data);
    }

    /**
     * @param int $id
     * @return PlayerFlag|null
     */
    public function get($id): ?PlayerFlag
    {
        if ($id) {
            $statement = $this->db->statement("SELECT * FROM `ss_players_flags` WHERE `id` = ?");
            $statement->bindAndExecute([$id]);

            if ($data = $statement->fetch()) {
                return $this->mapToModel($data);
            }
        }

        return null;
    }

    public function getByCredentials($serverId, $type, $authData): ?PlayerFlag
    {
        $statement = $this->db->statement(
            "SELECT * FROM `ss_players_flags` WHERE `server_id` = ? AND `type` = ? AND `auth_data` = ?"
        );
        $statement->bindAndExecute([$serverId, $type, $authData]);

        if ($data = $statement->fetch()) {
            return $this->mapToModel($data);
        }

        return null;
    }

    public function create($serverId, $type, $authData, $password, array $flags): PlayerFlag
    {
        $keys = "";
        $values = [];

        $filteredFlags = collect($flags)->filter(
            fn($value, $key) => in_array($key, PlayerFlag::FLAGS, true)
        );

        if ($filteredFlags->isPopulated()) {
            $keys =
                ", " .
                $filteredFlags
                    ->keys()
                    ->map(fn($key) => "`$key` = ?")
                    ->join(", ");

            $values = $filteredFlags->values()->all();
        }

        $this->db
            ->statement(
                "INSERT INTO `ss_players_flags` SET `server_id` = ?, `type` = ?, `auth_data` = ?, `password` = ? {$keys}"
            )
            ->bindAndExecute(array_merge([$serverId, $type, $authData, $password], $values));

        return $this->get($this->db->lastId());
    }

    public function deleteByCredentials($serverId, $type, $authData): void
    {
        $this->db
            ->statement(
                "DELETE FROM `ss_players_flags` " .
                    "WHERE `server_id` = ? AND `type` = ? AND `auth_data` = ?"
            )
            ->bindAndExecute([$serverId, $type, $authData]);
    }

    public function deleteOldFlags(): void
    {
        $this->db->query(
            <<<EOF
DELETE FROM `ss_players_flags`
WHERE (`a` < UNIX_TIMESTAMP() AND `a` != '-1')
AND (`b` < UNIX_TIMESTAMP() AND `b` != '-1')
AND (`c` < UNIX_TIMESTAMP() AND `c` != '-1')
AND (`d` < UNIX_TIMESTAMP() AND `d` != '-1')
AND (`e` < UNIX_TIMESTAMP() AND `e` != '-1')
AND (`f` < UNIX_TIMESTAMP() AND `f` != '-1')
AND (`g` < UNIX_TIMESTAMP() AND `g` != '-1')
AND (`h` < UNIX_TIMESTAMP() AND `h` != '-1')
AND (`i` < UNIX_TIMESTAMP() AND `i` != '-1')
AND (`j` < UNIX_TIMESTAMP() AND `j` != '-1')
AND (`k` < UNIX_TIMESTAMP() AND `k` != '-1')
AND (`l` < UNIX_TIMESTAMP() AND `l` != '-1')
AND (`m` < UNIX_TIMESTAMP() AND `m` != '-1')
AND (`n` < UNIX_TIMESTAMP() AND `n` != '-1')
AND (`o` < UNIX_TIMESTAMP() AND `o` != '-1')
AND (`p` < UNIX_TIMESTAMP() AND `p` != '-1')
AND (`q` < UNIX_TIMESTAMP() AND `q` != '-1')
AND (`r` < UNIX_TIMESTAMP() AND `r` != '-1')
AND (`s` < UNIX_TIMESTAMP() AND `s` != '-1')
AND (`t` < UNIX_TIMESTAMP() AND `t` != '-1')
AND (`u` < UNIX_TIMESTAMP() AND `u` != '-1')
AND (`v` < UNIX_TIMESTAMP() AND `v` != '-1')
AND (`w` < UNIX_TIMESTAMP() AND `w` != '-1')
AND (`x` < UNIX_TIMESTAMP() AND `x` != '-1')
AND (`y` < UNIX_TIMESTAMP() AND `y` != '-1')
AND (`z` < UNIX_TIMESTAMP() AND `z` != '-1')
EOF
        );
    }

    public function mapToModel(array $data): PlayerFlag
    {
        $flags = collect($data)
            ->filter(fn($value, $key) => in_array($key, PlayerFlag::FLAGS, true))
            ->mapWithKeys(fn($value) => as_int($value))
            ->all();

        return new PlayerFlag(
            as_int($data["id"]),
            as_int($data["server_id"]),
            as_int($data["type"]),
            as_string($data["auth_data"]),
            as_string($data["password"]),
            $flags
        );
    }
}
