<?php
namespace App;

use App\Exceptions\SqlQueryException;

class Database
{
    private $host;
    private $user;
    private $pass;
    private $name;
    private $port;

    private $link;

    private $error;
    private $errno;

    private $query;
    private $result;
    public $counter = 0;

    public function __construct($host, $port, $user, $pass, $name)
    {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
        $this->name = $name;
    }

    function __destruct()
    {
        $this->close();
    }

    /**
     * @throws SqlQueryException
     */
    public function connect()
    {
        $this->connectWithoutDb();
        $this->selectDb($this->name);
    }

    /**
     * @throws SqlQueryException
     */
    public function connectWithoutDb()
    {
        $this->link = mysqli_connect($this->host, $this->user, $this->pass, '', $this->port);
        if (!$this->link) {
            $this->error = mysqli_connect_error();
            $this->errno = mysqli_connect_errno();
            throw $this->exception("no_server_connection");
        }

        $this->query("SET NAMES utf8");
    }

    /**
     * @param string $name
     * @throws SqlQueryException
     */
    public function selectDb($name)
    {
        $result = mysqli_select_db($this->link, $name);
        if (!$result) {
            throw $this->exception("no_db_connection");
        }
    }

    public function close()
    {
        if (!$this->isConnected()) {
            return;
        }

        mysqli_close($this->link);
        $this->link = null;
    }

    public function prepare($query, $values)
    {
        // Escapeowanie wszystkich argumentów
        $i = 0;
        foreach ($values as $value) {
            $values[$i++] = $this->escape($value);
        }

        return vsprintf($query, $values);
    }

    /**
     * @param $query
     * @return \mysqli_result
     * @throws SqlQueryException
     */
    public function query($query)
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $this->counter += 1;
        $this->query = $query;

        if ($this->result = mysqli_query($this->link, $query)) {
            return $this->result;
        }

        throw $this->exception("query_error");
    }

    /**
     * @param string $query
     * @return bool
     * @throws SqlQueryException
     */
    public function multiQuery($query)
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $this->query = $query;
        if ($this->result = mysqli_multi_query($this->link, $query)) {
            return $this->result;
        }

        throw $this->exception("query_error");
    }

    /**
     * @param string $query
     * @param string $column
     * @return mixed|null
     * @throws SqlQueryException
     */
    public function getColumn($query, $column)
    {
        $this->query = $query;
        $result = $this->query($query);

        if (!$this->numRows($result)) {
            return null;
        }

        $row = $this->fetchArrayAssoc($result);
        if (!isset($row[$column])) {
            return null;
        }

        return $row[$column];
    }

    /**
     * @param \mysqli_result $result
     * @return int
     * @throws SqlQueryException
     */
    public function numRows($result)
    {
        if (empty($result)) {
            throw $this->exception("no_query_num_rows");
        }

        return mysqli_num_rows($result);
    }

    /**
     * @param \mysqli_result $result
     * @return array|null
     * @throws SqlQueryException
     */
    public function fetchArrayAssoc($result)
    {
        if (empty($result)) {
            throw $this->exception("no_query_fetch_array_assoc");
        }

        return mysqli_fetch_assoc($result);
    }

    /**
     * @param \mysqli_result $result
     * @return array|null
     * @throws SqlQueryException
     */
    public function fetchArray($result)
    {
        if (empty($result)) {
            throw $this->exception("no_query_fetch_array");
        }

        return mysqli_fetch_array($result);
    }

    public function lastId()
    {
        return mysqli_insert_id($this->link);
    }

    public function affectedRows()
    {
        return mysqli_affected_rows($this->link);
    }

    public function escape($str)
    {
        return mysqli_real_escape_string($this->link, $str);
    }

    public function getLastQuery()
    {
        return $this->query;
    }

    public function startTransaction()
    {
        mysqli_begin_transaction($this->link);
    }

    public function rollback()
    {
        mysqli_rollback($this->link);
    }

    public function dropAllTables()
    {
        $tables = $this->getAllTables();

        if (empty($tables)) {
            return;
        }

        $this->disableForeignKeyConstraints();
        $this->query('drop table ' . implode(',', $tables));
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
        $this->query("CREATE DATABASE IF NOT EXISTS $database");
    }

    /**
     * @param string $messageId
     * @return SqlQueryException
     */
    private function exception($messageId)
    {
        if ($this->link) {
            $this->error = mysqli_error($this->link);
            $this->errno = mysqli_errno($this->link);
        }

        return new SqlQueryException($messageId, $this->query, $this->error, $this->errno);
    }

    public function isConnected()
    {
        return !!$this->link;
    }
}
