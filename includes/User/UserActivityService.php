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

        $this->db->query(
            $this->db->prepare(
                "UPDATE `" .
                    TABLE_PREFIX .
                    "users` " .
                    "SET `lastactiv` = NOW(), `lastip` = '%s' " .
                    "WHERE `uid` = '%d'",
                [$user->getLastIp(), $user->getUid()]
            )
        );
    }
}
