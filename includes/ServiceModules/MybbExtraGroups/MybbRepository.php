<?php
namespace App\ServiceModules\MybbExtraGroups;

use App\Support\Database;
use PDOException;

class MybbRepository
{
    /** @var Database */
    private $dbMybb;

    public function __construct($host, $port, $user, $password, $database)
    {
        $this->dbMybb = new Database($host, $port, $user, $password, $database);
    }

    /**
     * @param int $uid
     * @return string|false
     */
    public function findUsernameByUid($uid)
    {
        $statement = $this->dbMybb()->statement(
            "SELECT `username` FROM `mybb_users` WHERE `uid` = ?"
        );
        $statement->execute([$uid]);
        return $statement->fetchColumn();
    }

    /**
     * @param int $uid
     * @return array|false
     */
    public function getUserByUid($uid)
    {
        $statement = $this->dbMybb()->statement(
            "SELECT `uid`, `additionalgroups`, `displaygroup`, `usergroup` " .
                "FROM `mybb_users` " .
                "WHERE `uid` = ?"
        );
        $statement->execute([$uid]);
        return $statement->fetch();
    }

    /**
     * @param string $username
     * @return array|false
     */
    public function getUserByUsername($username)
    {
        $statement = $this->dbMybb()->statement(
            "SELECT `uid`, `additionalgroups`, `displaygroup`, `usergroup` " .
                "FROM `mybb_users` " .
                "WHERE `username` = ?"
        );
        $statement->execute([$username]);
        return $statement->fetch();
    }

    /**
     * @param string $username
     * @return bool
     */
    public function existsByUsername($username)
    {
        $statement = $this->dbMybb()->statement("SELECT 1 FROM `mybb_users` WHERE `username` = ?");
        $statement->execute([$username]);
        return $statement->rowCount() > 0;
    }

    /**
     * @param int $uid
     * @param array $additionalGroups
     * @param int $displayGroup
     */
    public function updateGroups($uid, array $additionalGroups, $displayGroup)
    {
        $this->dbMybb()
            ->statement(
                "UPDATE `mybb_users` " .
                    "SET `additionalgroups` = ?, `displaygroup` = ? " .
                    "WHERE `uid` = ?"
            )
            ->execute([implode(",", $additionalGroups), $displayGroup, $uid]);
    }

    /**
     * @return Database
     * @throws PDOException
     */
    private function dbMybb()
    {
        if (!$this->dbMybb->isConnected()) {
            $this->connectDb();
        }

        return $this->dbMybb;
    }

    /**
     * @throws PDOException
     */
    public function connectDb()
    {
        $this->dbMybb->connect();
    }
}
