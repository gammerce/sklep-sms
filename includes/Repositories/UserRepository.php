<?php
namespace App\Repositories;

use App\Database;
use App\Models\User;

class UserRepository
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create($username, $password, $email, $forename, $surname, $ip, $groups = '1')
    {
        $salt = get_random_string(8);
        $this->db->query(
            $this->db->prepare(
                "INSERT INTO `" .
                    TABLE_PREFIX .
                    "users` (`username`, `password`, `salt`, `email`, `forename`, `surname`, `regip`, `groups`, `regdate`) " .
                    "VALUES ('%s','%s','%s','%s','%s','%s','%s','%s', NOW())",
                [
                    $username,
                    hash_password($password, $salt),
                    $salt,
                    $email,
                    $forename,
                    $surname,
                    $ip,
                    $groups,
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
                    "SET `username` = '%s', `forename` = '%s', `surname` = '%s', `email` = '%s', `groups` = '%s', `wallet` = '%d' " .
                    "WHERE `uid` = '%d'",
                [
                    $user->getUsername(false),
                    $user->getForename(false),
                    $user->getSurname(false),
                    $user->getEmail(false),
                    implode(";", $user->getGroups()),
                    $user->getWallet(),
                    $user->getUid(),
                ]
            )
        );
    }
}
