<?php
namespace App\Repositories;

use App\Models\User;
use App\System\Database;

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
        $this->db
            ->statement(
                "INSERT INTO `ss_users` (`username`, `password`, `salt`, `email`, `forename`, `surname`, `regip`, `groups`, `wallet`, `steam_id`, `regdate`) " .
                    "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            )
            ->execute([
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
                $user->getUsername(),
                $user->getForename(),
                $user->getSurname(),
                $user->getEmail(),
                implode(";", $user->getGroups()),
                $user->getWallet(),
                $user->getSteamId(),
                $user->getUid(),
            ]);
    }

    /**
     * @return User[]
     */
    public function allWithSteamId()
    {
        $result = $this->db->query("SELECT * FROM `ss_users` WHERE `steam_id` != ''");

        $users = [];
        foreach ($result as $row) {
            $users[] = $this->mapToModel($row);
        }

        return $users;
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

    public function createResetPasswordKey($uid)
    {
        $key = get_random_string(32);
        $this->db
            ->statement("UPDATE `ss_users` " . "SET `reset_password_key` = ? WHERE `uid` = ?")
            ->execute([$key, $uid]);

        return $key;
    }

    public function updatePassword($uid, $password)
    {
        if (is_demo() && as_int($uid) === 1) {
            // Do not allow to modify admin's password in demo version
            return;
        }

        $salt = get_random_string(8);

        $this->db
            ->statement(
                "UPDATE `ss_users` SET `password` = ?, `salt` = ?, `reset_password_key` = '' WHERE `uid` = ?"
            )
            ->execute([hash_password($password, $salt), $salt, $uid]);
    }

    public function touch(User $user)
    {
        $this->db
            ->statement("UPDATE `ss_users` SET `lastactiv` = NOW(), `lastip` = ? WHERE `uid` = ?")
            ->execute([$user->getLastIp(), $user->getUid()]);
    }

    public function delete($id)
    {
        $statement = $this->db->statement("DELETE FROM `ss_users` WHERE `uid` = ?");
        $statement->execute([$id]);

        return !!$statement->rowCount();
    }

    private function mapToModel(array $data)
    {
        return new User(
            as_int($data['uid']),
            $data['username'],
            $data['password'],
            $data['salt'],
            $data['email'],
            $data['forename'],
            $data['surname'],
            $data['steam_id'],
            explode(';', $data['groups']),
            $data['regdate'],
            $data['lastactiv'],
            (int) $data['wallet'],
            $data['regip'],
            $data['lastip'],
            $data['reset_password_key']
        );
    }
}
