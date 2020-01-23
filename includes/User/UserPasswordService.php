<?php
namespace App\User;

use App\System\Database;

class UserPasswordService
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * @param int $uid
     * @param string $password
     */
    public function change($uid, $password)
    {
        $salt = get_random_string(8);

        $this->db
            ->statement(
                "UPDATE `" .
                    TABLE_PREFIX .
                    "users` " .
                    "SET `password` = ?, `salt` = ?, `reset_password_key` = '' " .
                    "WHERE `uid` = ?"
            )
            ->execute([hash_password($password, $salt), $salt, $uid]);
    }
}
