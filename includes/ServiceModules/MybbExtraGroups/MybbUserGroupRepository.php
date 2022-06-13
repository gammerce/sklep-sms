<?php
namespace App\ServiceModules\MybbExtraGroups;

use App\Support\Database;
use App\Support\QueryParticle;

class MybbUserGroupRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function createMany(array $rows): void
    {
        if (!$rows) {
            return;
        }

        $queryParticle = new QueryParticle();

        foreach ($rows as $row) {
            if ($row["expire"] === null) {
                $queryParticle->add("(?, ?, NULL, ?)", [
                    $row["uid"],
                    $row["gid"],
                    $row["was_before"] ? 1 : 0,
                ]);
            } else {
                $queryParticle->add("(?, ?, FROM_UNIXTIME(UNIX_TIMESTAMP() + ?), ?)", [
                    $row["uid"],
                    $row["gid"],
                    $row["expire"],
                    $row["was_before"] ? 1 : 0,
                ]);
            }
        }

        $this->db
            ->statement(
                "INSERT INTO `ss_mybb_user_group` (`uid`, `gid`, `expire`, `was_before`) VALUES " .
                    $queryParticle->text(", ")
            )
            ->bindAndExecute($queryParticle->params());
    }

    /**
     * @param int $id
     */
    public function delete($id): void
    {
        $this->db
            ->statement("DELETE FROM `ss_mybb_user_group` WHERE `uid` = ?")
            ->bindAndExecute([$id]);
    }

    /**
     * @param int $id
     * @return array
     */
    public function findGroupsExpiration($id): array
    {
        $statement = $this->db->statement(
            "SELECT `gid`, UNIX_TIMESTAMP(`expire`) - UNIX_TIMESTAMP() AS `expire`, `was_before` FROM `ss_mybb_user_group` " .
                "WHERE `uid` = ?"
        );
        $statement->bindAndExecute([$id]);
        return $statement->fetchAll();
    }
}
