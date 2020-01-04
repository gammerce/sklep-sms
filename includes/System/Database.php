<?php
namespace App\System;

use PDO;
use PDOException;
use PDOStatement;

class Database
{
    /** @var string */
    private $host;

    /** @var string */
    private $user;

    /** @var string */
    private $pass;

    /** @var string */
    private $name;

    /** @var string */
    private $port;

    /** @var PDO */
    private $pdo;

    public function __construct($host, $port, $user, $pass, $name)
    {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
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
        $dsn = "mysql:host={$this->host};port={$this->port};charset=utf8";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
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

    public function prepare($query, $values)
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $i = 0;
        foreach ($values as $value) {
            $values[$i++] = $this->escape($value);
        }

        return vsprintf($query, $values);
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

    /**
     * @param string $query
     * @param string $column
     * @return mixed|null
     * @throws PDOException
     */
    public function getColumn($query, $column)
    {
        $statement = $this->query($query);
        $row = $statement->fetch();
        return array_get($row, $column);
    }

    public function numRows(PDOStatement $statement)
    {
        return $statement->rowCount();
    }

    public function fetchArrayAssoc(PDOStatement $statement)
    {
        return $statement->fetch();
    }

    public function lastId()
    {
        return $this->pdo->lastInsertId();
    }

    public function escape($str)
    {
        $quote = $this->pdo->quote($str);
        return preg_replace("/(^'|'$)/", '', $quote);
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
        $this->query('DROP TABLE ' . implode(',', $tables));
        $this->enableForeignKeyConstraints();
    }

    public function getAllTables()
    {
        $tables = [];
        $result = $this->query('SHOW FULL TABLES WHERE table_type = \'BASE TABLE\'');

        while ($row = $this->fetchArrayAssoc($result)) {
            $row = (array) $row;
            $tables[] = reset($row);
        }

        return $tables;
    }

    public function disableForeignKeyConstraints()
    {
        $this->query('SET FOREIGN_KEY_CHECKS=0;');
    }

    public function enableForeignKeyConstraints()
    {
        $this->query('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function createDatabaseIfNotExists($database)
    {
        $this->query("CREATE DATABASE IF NOT EXISTS `$database`");
    }

    public function isConnected()
    {
        return $this->pdo !== null;
    }
}
