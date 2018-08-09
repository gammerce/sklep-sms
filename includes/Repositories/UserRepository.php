<?php
namespace App\Repositories;

use App\Database;
use App\Models\User;

class UserRepository
{
    /** @var Database */
    protected $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create($username, $password, $email, $forename, $surname, $ip, $groups = '1')
    {
        $salt = get_random_string(8);
        $this->db->query($this->db->prepare(
            "INSERT INTO `" . TABLE_PREFIX . "users` (`username`, `password`, `salt`, `email`, `forename`, `surname`, `regip`, `groups`) " .
            "VALUES ('%s','%s','%s','%s','%s','%s','%s','%s')",
            [$username, hash_password($password, $salt), $salt, $email, $forename, $surname, $ip, $groups]
        ));

        $id = $this->db->last_id();

        return new User($id);
    }
}