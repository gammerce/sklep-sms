<?php
namespace App\Repositories;

use App\Managers\GroupManager;
use App\Models\Group;
use App\Models\User;
use App\Payment\General\BillingAddress;
use App\Support\Database;
use App\Support\Money;
use App\User\Permission;
use PDOException;

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
        BillingAddress $billingAddress,
        $ip,
        $groups,
        $wallet = 0
    ): User {
        $salt = get_random_string(8);
        $this->db
            ->statement(
                <<<EOF
                INSERT INTO `ss_users` (
                    `username`,
                    `password`,
                    `salt`,
                    `email`,
                    `forename`,
                    `surname`,
                    `regip`,
                    `groups`,
                    `wallet`,
                    `steam_id`,
                    `billing_address`,
                    `regdate`
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
EOF
            )
            ->bindAndExecute([
                (string) $username,
                hash_password($password, $salt),
                $salt,
                (string) $email,
                (string) $forename,
                (string) $surname,
                (string) $ip,
                $groups,
                (int) $wallet,
                to_canonical_steam_id($steamId),
                json_encode($billingAddress->toArray()),
            ]);

        return $this->get($this->db->lastId());
    }

    /**
     * Persists the mutable profile fields. Steam verification is NOT written
     * here; it is owned by linkSteamId()/clearSteamId()/clearSteamVerification().
     */
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
            ->bindAndExecute([
                (string) $user->getUsername(),
                (string) $user->getForename(),
                (string) $user->getSurname(),
                (string) $user->getEmail(),
                implode(";", $user->getGroups()),
                $user->getWallet()->asInt(),
                to_canonical_steam_id($user->getSteamId()),
                json_encode($user->getBillingAddress()->toArray()),
                $user->getId(),
            ]);
    }

    /**
     * @param int|null $id
     * @return User|null
     */
    public function get($id): ?User
    {
        if ($id) {
            $statement = $this->db->statement("SELECT * FROM `ss_users` WHERE `uid` = ?");
            $statement->bindAndExecute([$id]);

            if ($data = $statement->fetch()) {
                return $this->mapToModel($data);
            }
        }

        return null;
    }

    /**
     * Looks a user up by Steam ID in any format (SteamID64, STEAM_x, STEAM3).
     * The stored value is the canonical SteamID64, so inputs are canonicalised.
     *
     * @param string $steamId
     * @return User|null
     */
    public function findBySteamId($steamId): ?User
    {
        $canonical = to_canonical_steam_id($steamId);

        if ($canonical === null) {
            return null;
        }

        $statement = $this->db->statement("SELECT * FROM `ss_users` WHERE `steam_id` = ?");
        $statement->bindAndExecute([$canonical]);

        $data = $statement->fetch();
        return $data ? $this->mapToModel($data) : null;
    }

    /**
     * Creates a passwordless account for a user who signed in through Steam.
     *
     * The empty password can never match a login (hash_password always returns a
     * hash), so it marks the account as Steam-only without a dedicated column.
     */
    public function createSteamUser(
        $username,
        $steamId,
        $email,
        $ip,
        $forename = "",
        $surname = ""
    ): User {
        try {
            $this->db
                ->statement(
                    <<<EOF
                    INSERT INTO `ss_users` (
                        `username`,
                        `password`,
                        `salt`,
                        `email`,
                        `forename`,
                        `surname`,
                        `regip`,
                        `groups`,
                        `wallet`,
                        `steam_id`,
                        `steam_verified`,
                        `billing_address`,
                        `regdate`
                    )
                    VALUES (?, '', '', ?, ?, ?, ?, '1', 0, ?, 1, '{}', NOW())
EOF
                )
                ->bindAndExecute([
                    (string) $username,
                    (string) $email,
                    (string) $forename,
                    (string) $surname,
                    (string) $ip,
                    to_canonical_steam_id($steamId),
                ]);
        } catch (PDOException $e) {
            // A concurrent request may have created the account for this Steam ID
            // between our lookup and insert; reuse it instead of failing.
            if (get_error_code($e) !== 1062) {
                throw $e;
            }

            $existing = $this->findBySteamId($steamId);
            if ($existing) {
                return $existing;
            }

            throw $e;
        }

        return $this->get($this->db->lastId());
    }

    /**
     * Attaches a verified Steam ID to an existing account.
     */
    public function linkSteamId($userId, $steamId): void
    {
        $this->db
            ->statement(
                "UPDATE `ss_users` SET `steam_id` = ?, `steam_verified` = 1 WHERE `uid` = ?"
            )
            ->bindAndExecute([to_canonical_steam_id($steamId), $userId]);
    }

    /**
     * Removes a Steam link. This is the only way to change a verified Steam ID.
     */
    public function clearSteamId($userId): void
    {
        $this->db
            ->statement(
                "UPDATE `ss_users` SET `steam_id` = NULL, `steam_verified` = 0 WHERE `uid` = ?"
            )
            ->bindAndExecute([$userId]);
    }

    /**
     * Drops verification (e.g. an admin replaced the Steam ID manually) while
     * keeping the entered value.
     */
    public function clearSteamVerification($userId): void
    {
        $this->db
            ->statement("UPDATE `ss_users` SET `steam_verified` = 0 WHERE `uid` = ?")
            ->bindAndExecute([$userId]);
    }

    /**
     * Stores a pending e-mail change. It is applied only after confirmation.
     */
    public function setEmailChange($userId, $email, $key): void
    {
        $this->db
            ->statement(
                "UPDATE `ss_users` SET `email_change_new` = ?, `email_change_key` = ? WHERE `uid` = ?"
            )
            ->bindAndExecute([(string) $email, (string) $key, $userId]);
    }

    /**
     * @param string $key
     * @return User|null
     */
    public function findByEmailChangeKey($key): ?User
    {
        if (!strlen($key)) {
            return null;
        }

        $statement = $this->db->statement("SELECT * FROM `ss_users` WHERE `email_change_key` = ?");
        $statement->bindAndExecute([$key]);

        $data = $statement->fetch();
        return $data ? $this->mapToModel($data) : null;
    }

    public function getPendingEmail($userId): ?string
    {
        $statement = $this->db->statement(
            "SELECT `email_change_new` FROM `ss_users` WHERE `uid` = ?"
        );
        $statement->bindAndExecute([$userId]);

        $value = $statement->fetchColumn();
        return $value === false ? null : (string) $value;
    }

    /**
     * Applies the pending e-mail change. Returns false when the target address is
     * already taken (unique constraint), leaving the pending change in place.
     */
    public function confirmEmailChange($userId): bool
    {
        try {
            $statement = $this->db->statement(
                "UPDATE `ss_users` SET `email` = `email_change_new`, `email_change_new` = '', `email_change_key` = '' WHERE `uid` = ? AND `email_change_key` <> ''"
            );
            $statement->bindAndExecute([$userId]);
        } catch (PDOException $e) {
            // Only "target e-mail already taken" is a normal outcome.
            if (get_error_code($e) === 1062) {
                return false;
            }

            throw $e;
        }

        return $statement->rowCount() > 0;
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
        $statement->bindAndExecute([$username]);

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
        $statement->bindAndExecute([$email]);

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
        $statement->bindAndExecute([$emailOrUsername, $emailOrUsername, $password]);

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
        $statement->bindAndExecute([$resetKey]);

        $data = $statement->fetch();
        return $data ? $this->mapToModel($data) : null;
    }

    public function createResetPasswordKey($userId): string
    {
        $key = get_random_string(32);
        $this->db
            ->statement("UPDATE `ss_users` SET `reset_password_key` = ? WHERE `uid` = ?")
            ->bindAndExecute([$key, $userId]);

        return $key;
    }

    public function updatePassword($userId, $password): void
    {
        $salt = get_random_string(8);

        $this->db
            ->statement(
                "UPDATE `ss_users` SET `password` = ?, `salt` = ?, `reset_password_key` = '' WHERE `uid` = ?"
            )
            ->bindAndExecute([hash_password($password, $salt), $salt, $userId]);
    }

    public function touch(User $user): void
    {
        $this->db
            ->statement("UPDATE `ss_users` SET `lastactiv` = NOW(), `lastip` = ? WHERE `uid` = ?")
            ->bindAndExecute([$user->getLastIp(), $user->getId()]);
    }

    public function delete($id): bool
    {
        $statement = $this->db->statement("DELETE FROM `ss_users` WHERE `uid` = ?");
        $statement->bindAndExecute([$id]);

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
            to_legacy_steam_id($data["steam_id"]),
            $billingAddress,
            $groupsIds,
            $data["regdate"],
            $data["lastactiv"],
            new Money($data["wallet"]),
            $data["regip"],
            $data["lastip"],
            $data["reset_password_key"],
            $permissions,
            boolval($data["steam_verified"] ?? false)
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
