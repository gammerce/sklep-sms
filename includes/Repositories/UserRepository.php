<?php
namespace App\Repositories;

use App\Managers\GroupManager;
use App\Models\Group;
use App\Models\User;
use App\Payment\General\BillingAddress;
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
    ): User {
        $salt = get_random_string(8);
        $this->db
            ->statement(
                <<<EOF
                INSERT INTO `ss_users` (`username`, `password`, `salt`, `email`, `forename`, `surname`, `regip`, `groups`, `wallet`, `steam_id`, `regdate`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
EOF
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

    public function update(User $user): void
    {
        $this->db
            ->statement(
                <<<EOF
                UPDATE `ss_users` 
                SET `username` = ?,
                    `forename` = ?,
                    `surname` = ?,
                    `email` = ?,
                    `groups` = ?,
                    `wallet` = ?,
                    `steam_id` = ?,
                    `billing_address` = ?
                WHERE `uid` = ?
EOF
            )
            ->execute([
                (string) $user->getUsername(),
                (string) $user->getForename(),
                (string) $user->getSurname(),
                (string) $user->getEmail(),
                implode(";", $user->getGroups()),
                $user->getWallet()->asInt(),
                $user->getSteamId() ?: null,
                json_encode($user->getBillingAddress()->toArray()),
                $user->getId(),
            ]);
    }

    /**
     * @return User[]
     */
    public function allWithSteamId(): array
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
    public function get($id): ?User
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
    public function findBySteamId($steamId): ?User
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
    public function findByUsername($username): ?User
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
    public function findByEmail($email): ?User
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
    public function findByPassword($emailOrUsername, $password): ?User
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
    public function findByResetKey($resetKey): ?User
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

    public function createResetPasswordKey($userId): string
    {
        $key = get_random_string(32);
        $this->db
            ->statement("UPDATE `ss_users` SET `reset_password_key` = ? WHERE `uid` = ?")
            ->execute([$key, $userId]);

        return $key;
    }

    public function updatePassword($userId, $password): void
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

    public function touch(User $user): void
    {
        $this->db
            ->statement("UPDATE `ss_users` SET `lastactiv` = NOW(), `lastip` = ? WHERE `uid` = ?")
            ->execute([$user->getLastIp(), $user->getId()]);
    }

    public function delete($id): bool
    {
        $statement = $this->db->statement("DELETE FROM `ss_users` WHERE `uid` = ?");
        $statement->execute([$id]);

        return !!$statement->rowCount();
    }

    public function mapToModel(array $data): User
    {
        $groupsIds = explode_int_list($data["groups"], ";");
        $billingAddress = BillingAddress::fromArray(json_decode($data["billing_address"], true));
        $permissions = $this->gatherPermissions($groupsIds);

        return new User(
            as_int($data["uid"]),
            $data["username"],
            $data["password"],
            $data["salt"],
            $data["email"],
            $data["forename"],
            $data["surname"],
            $data["steam_id"],
            $billingAddress,
            $groupsIds,
            $data["regdate"],
            $data["lastactiv"],
            new Money($data["wallet"]),
            $data["regip"],
            $data["lastip"],
            $data["reset_password_key"],
            $permissions
        );
    }

    /**
     * @param int[] $groupsIds
     * @return Permission[]
     */
    private function gatherPermissions(array $groupsIds): array
    {
        return collect($groupsIds)
            ->map(fn($groupId) => $this->groupManager->get($groupId))
            ->filter(fn($group) => $group)
            ->flatMap(fn(Group $group) => $group->getPermissions())
            ->unique()
            ->all();
    }
}
