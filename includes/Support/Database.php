<?php
namespace App\Support;

use PDO;
use PDOException;

class Database
{
    /** @var string */
    private $host;

    /** @var string */
    private $user;

    /** @var string */
    private $password;

    /** @var string */
    private $name;

    /** @var string */
    private $port;

    /** @var PDO */
    private $pdo;

    public function __construct($host, $port, $user, $password, $name)
    {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->password = $password;
        $this->name = $name;
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @throws PDOException
     */
    public function connect()
    {
        $this->connectWithoutDb();
        $this->selectDb($this->name);
    }

    /**
     * @throws PDOException
     */
    public function connectWithoutDb()
    {
        $dsn = "mysql:host={$this->host};port={$this->port};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_STATEMENT_CLASS => [PDOStatement::class],
        ];
        $this->pdo = new PDO($dsn, $this->user, $this->password, $options);
    }

    /**
     * @param string $name
     * @throws PDOException
     */
    public function selectDb($name)
    {
        $this->pdo->exec("USE `$name`");
    }

    public function close()
    {
        $this->pdo = null;
    }

    /**
     * @param string $query
     * @return PDOStatement
     * @throws PDOException
     */
    public function query($query)
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        return $this->pdo->query($query);
    }

    public function statement($statement)
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        return $this->pdo->prepare($statement);
    }

    public function lastId()
    {
        return (int) $this->pdo->lastInsertId();
    }

    public function startTransaction()
    {
        $this->pdo->beginTransaction();
    }

    public function rollback()
    {
        $this->pdo->rollBack();
    }

    public function dropAllTables()
    {
        $tables = $this->getAllTables();

        if (empty($tables)) {
            return;
        }

        $this->disableForeignKeyConstraints();
        $this->query("DROP TABLE " . implode(",", $tables));
        $this->enableForeignKeyConstraints();
    }

    public function getAllTables()
    {
        $tables = [];
        $result = $this->query('SHOW FULL TABLES WHERE table_type = \'BASE TABLE\'');

        foreach ($result as $row) {
            $row = (array) $row;
            $tables[] = reset($row);
        }

        return $tables;
    }

    public function disableForeignKeyConstraints()
    {
        $this->query("SET FOREIGN_KEY_CHECKS=0;");
    }

    public function enableForeignKeyConstraints()
    {
        $this->query("SET FOREIGN_KEY_CHECKS=1;");
    }

    public function createDatabaseIfNotExists($database)
    {
        $this->query("CREATE DATABASE IF NOT EXISTS `$database`");
    }

    public function dropDatabaseIfExists($database)
    {
        $this->query("DROP DATABASE IF EXISTS `$database`");
    }

    /**
     * @return int
     */
    public function getNow()
    {
        return $this->query("SELECT UNIX_TIMESTAMP(NOW())")->fetchColumn();
    }

    public function isConnected()
    {
        return $this->pdo !== null;
    }
}
