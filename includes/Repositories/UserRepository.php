<?php
namespace App\Repositories;

use App\System\Database;
use App\Models\User;

class UserRepository
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create(
        $username,
        $password,
        $email,
        $forename,
        $surname,
        $steamId,
        $ip,
        $groups,
        $wallet = 0
    ) {
        $salt = get_random_string(8);
        $this->db->query(
            $this->db->prepare(
                "INSERT INTO `" .
                    TABLE_PREFIX .
                    "users` (`username`, `password`, `salt`, `email`, `forename`, `surname`, `regip`, `groups`, `wallet`, `steam_id`, `regdate`) " .
                    "VALUES ('%s','%s','%s','%s','%s','%s','%s','%s','%d','%s',NOW())",
                [
                    $username,
                    hash_password($password, $salt),
                    $salt,
                    $email,
                    $forename,
                    $surname,
                    $ip,
                    $groups,
                    $wallet,
                    $steamId,
                ]
            )
        );

        $id = $this->db->lastId();

        return new User($id);
    }

    public function update(User $user)
    {
        $this->db->query(
            $this->db->prepare(
                "UPDATE `" .
                    TABLE_PREFIX .
                    "users` " .
                    "SET `username` = '%s', `forename` = '%s', `surname` = '%s', `email` = '%s', `groups` = '%s', `wallet` = '%d', `steam_id` = '%s' " .
                    "WHERE `uid` = '%d'",
                [
                    $user->getUsername(false),
                    $user->getForename(),
                    $user->getSurname(),
                    $user->getEmail(false),
                    implode(";", $user->getGroups()),
                    $user->getWallet(),
                    $user->getSteamId(),
                    $user->getUid(),
                ]
            )
        );
    }

    /**
     * @param int $id
     * @return User|null
     * @throws \App\Exceptions\SqlQueryException
     */
    public function get($id)
    {
        $result = $this->db->query(
            $this->db->prepare(
                "SELECT * FROM `" . TABLE_PREFIX . "users` WHERE `uid` = '%d'",
                [$id]
            )
        );

        if ($this->db->numRows($result)) {
            $data = $this->db->fetchArrayAssoc($result);
            return $this->resultToObject($data);
        }

        return null;
    }

    /**
     * @param string $steamId
     * @return User|null
     * @throws \App\Exceptions\SqlQueryException
     */
    public function findBySteamId($steamId)
    {
        if (!strlen($steamId)) {
            return null;
        }

        $result = $this->db->query(
            $this->db->prepare(
                "SELECT * FROM `" . TABLE_PREFIX . "users` WHERE `steam_id` = '%s'",
                [$steamId]
            )
        );

        if ($this->db->numRows($result)) {
            $data = $this->db->fetchArrayAssoc($result);
            return $this->resultToObject($data);
        }

        return null;
    }

    private function resultToObject($data)
    {
        return new User($data['id']);
    }
}
