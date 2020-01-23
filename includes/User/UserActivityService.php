<?php
namespace App\User;

use App\Models\User;
use App\System\Database;

class UserActivityService
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function update(User $user)
    {
        if (!$user->exists()) {
            return;
        }

        $this->db
            ->statement(
                "UPDATE `" .
                    TABLE_PREFIX .
                    "users` " .
                    "SET `lastactiv` = NOW(), `lastip` = ? " .
                    "WHERE `uid` = ?"
            )
            ->execute([$user->getLastIp(), $user->getUid()]);
    }
}
