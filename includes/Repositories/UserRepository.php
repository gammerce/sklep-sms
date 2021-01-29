<?php
namespace App\Repositories;

use App\Managers\GroupManager;
use App\Models\User;
use App\Support\Database;
use App\Support\Money;
use App\User\Permission;

class UserRepository
{
    private Database $db;
    private GroupManager $groupManager;

    public function __construct(Database $db, GroupManager $groupManager)
    {
        $this->db = $db;
        $this->groupManager = $groupManager;
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
        $this->db
            ->statement(
                "INSERT INTO `ss_users` (`username`, `password`, `salt`, `email`, `forename`, `surname`, `regip`, `groups`, `wallet`, `steam_id`, `regdate`) " .
                    "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            )
            ->execute([
                (string) $username,
                hash_password($password, $salt),
                $salt,
                (string) $email,
                (string) $forename,
                (string) $surname,
                (string) $ip,
                $groups,
                (int) $wallet,
                $steamId ?: null,
            ]);

        return $this->get($this->db->lastId());
    }

    public function update(User $user)
    {
        $this->db
            ->statement(
                "UPDATE `ss_users` " .
                    "SET `username` = ?, `forename` = ?, `surname` = ?, `email` = ?, `groups` = ?, `wallet` = ?, `steam_id` = ? " .
                    "WHERE `uid` = ?"
            )
            ->execute([
                (string) $user->getUsername(),
                (string) $user->getForename(),
                (string) $user->getSurname(),
                (string) $user->getEmail(),
                implode(";", $user->getGroups()),
                $user->getWallet()->asInt(),
                $user->getSteamId() ?: null,
                $user->getId(),
            ]);
    }

    /**
     * @return User[]
     */
    public function allWithSteamId()
    {
        $statement = $this->db->query("SELECT * FROM `ss_users` WHERE `steam_id` IS NOT NULL");
        return collect($statement)
            ->map(fn(array $row) => $this->mapToModel($row))
            ->all();
    }

    /**
     * @param int $id
     * @return User|null
     */
    public function get($id)
    {
        if ($id) {
            $statement = $this->db->statement("SELECT * FROM `ss_users` WHERE `uid` = ?");
            $statement->execute([$id]);

            if ($data = $statement->fetch()) {
                return $this->mapToModel($data);
            }
        }

        return null;
    }

    /**
     * @param string $steamId
     * @return User|null
     */
    public function findBySteamId($steamId)
    {
        if (!strlen($steamId)) {
            return null;
        }

        // SID can start with STEAM_0 or STEAM_1. They are used interchangeably.
        $steamIdSuffix = preg_replace("/^STEAM_[01]/", "", $steamId);

        $statement = $this->db->statement("SELECT * FROM `ss_users` WHERE `steam_id` IN (?, ?)");
        $statement->execute(["STEAM_0{$steamIdSuffix}", "STEAM_1{$steamIdSuffix}"]);

        $data = $statement->fetch();
        return $data ? $this->mapToModel($data) : null;
    }

    /**
     * @param string $username
     * @return User|null
     */
    public function findByUsername($username)
    {
        if (!strlen($username)) {
            return null;
        }

        $statement = $this->db->statement("SELECT * FROM `ss_users` WHERE `username` = ?");
        $statement->execute([$username]);

        $data = $statement->fetch();
        return $data ? $this->mapToModel($data) : null;
    }

    /**
     * @param string $email
     * @return User|null
     */
    public function findByEmail($email)
    {
        if (!strlen($email)) {
            return null;
        }

        $statement = $this->db->statement("SELECT * FROM `ss_users` WHERE `email` = ?");
        $statement->execute([$email]);

        $data = $statement->fetch();
        return $data ? $this->mapToModel($data) : null;
    }

    /**
     * @param string $emailOrUsername
     * @param string $password
     * @return User|null
     */
    public function findByPassword($emailOrUsername, $password)
    {
        if (!strlen($emailOrUsername) || !strlen($password)) {
            return null;
        }

        $statement = $this->db->statement(
            "SELECT * FROM `ss_users` " .
                "WHERE (`username` = ? OR `email` = ?) AND `password` = md5(CONCAT(md5(?), md5(`salt`)))"
        );
        $statement->execute([$emailOrUsername, $emailOrUsername, $password]);

        $data = $statement->fetch();
        return $data ? $this->mapToModel($data) : null;
    }

    /**
     * @param string $resetKey
     * @return User|null
     */
    public function findByResetKey($resetKey)
    {
        if (!strlen($resetKey)) {
            return null;
        }

        $statement = $this->db->statement(
            "SELECT * FROM `ss_users` WHERE `reset_password_key` = ?"
        );
        $statement->execute([$resetKey]);

        $data = $statement->fetch();
        return $data ? $this->mapToModel($data) : null;
    }

    public function createResetPasswordKey($userId)
    {
        $key = get_random_string(32);
        $this->db
            ->statement("UPDATE `ss_users` SET `reset_password_key` = ? WHERE `uid` = ?")
            ->execute([$key, $userId]);

        return $key;
    }

    public function updatePassword($userId, $password)
    {
        if (is_demo() && as_int($userId) === 1) {
            // Do not allow to modify admin's password in demo version
            return;
        }

        $salt = get_random_string(8);

        $this->db
            ->statement(
                "UPDATE `ss_users` SET `password` = ?, `salt` = ?, `reset_password_key` = '' WHERE `uid` = ?"
            )
            ->execute([hash_password($password, $salt), $salt, $userId]);
    }

    public function touch(User $user)
    {
        $this->db
            ->statement("UPDATE `ss_users` SET `lastactiv` = NOW(), `lastip` = ? WHERE `uid` = ?")
            ->execute([$user->getLastIp(), $user->getId()]);
    }

    public function delete($id)
    {
        $statement = $this->db->statement("DELETE FROM `ss_users` WHERE `uid` = ?");
        $statement->execute([$id]);

        return !!$statement->rowCount();
    }

    public function mapToModel(array $data)
    {
        $groupsIds = explode_int_list($data["groups"], ";");

        return new User(
            as_int($data["uid"]),
            $data["username"],
            $data["password"],
            $data["salt"],
            $data["email"],
            $data["forename"],
            $data["surname"],
            $data["steam_id"],
            $groupsIds,
            $data["regdate"],
            $data["lastactiv"],
            new Money($data["wallet"]),
            $data["regip"],
            $data["lastip"],
            $data["reset_password_key"],
            $this->gatherPermissions($groupsIds)
        );
    }

    /**
     * @param int[] $groupsIds
     * @return Permission[]
     */
    private function gatherPermissions(array $groupsIds)
    {
        return collect($groupsIds)
            ->flatMap(function ($groupId) {
                $group = $this->groupManager->get($groupId);
                return $group->getPermissions();
            })
            ->unique()
            ->all();
    }
}
