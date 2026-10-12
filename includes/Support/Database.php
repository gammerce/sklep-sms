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
    public function connect(): void
    {
        $this->connectWithoutDb();
        $this->selectDb($this->name);
    }

    /**
     * @throws PDOException
     */
    public function connectWithoutDb(): void
    {
        $dsn = "mysql:host={$this->host};port={$this->port};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_STATEMENT_CLASS => [PDOStatement::class],
            // Bound LIMIT ?, ? params rely on emulated prepares. Native prepares
            // are supported as well (verified on MySQL 8.4); the toggle exists
            // so it can be proven with DB_EMULATE_PREPARES=0. Defaults to true
            // to preserve long-standing behavior (incl. MySQL 5.7).
            PDO::ATTR_EMULATE_PREPARES => $this->shouldEmulatePrepares(),
        ];
        $this->pdo = new PDO($dsn, $this->user, $this->password, $options);
        // Explicit connection charset/collation so behavior is identical
        // regardless of server defaults (5.7: utf8mb4_general_ci,
        // 8.x: utf8mb4_0900_ai_ci). utf8mb4_bin matches the column collations.
        $this->pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_bin");
    }

    /**
     * @param string $name
     * @throws PDOException
     */
    public function selectDb($name): void
    {
        $this->pdo->exec("USE `$name`");
    }

    public function close(): void
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

    public function lastId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    public function startTransaction(): void
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $this->pdo->beginTransaction();
    }

    public function rollback(): void
    {
        if ($this->isConnected()) {
            $this->pdo->rollBack();
        }
    }

    public function dropAllTables(): void
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
        // Explicit charset/collation so fresh installs behave the same
        // regardless of server defaults (5.7 defaults to latin1,
        // 8.x defaults to utf8mb4). No-op for existing databases.
        $this->query("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_bin");
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

    public function isConnected(): bool
    {
        return $this->pdo !== null;
    }

    /**
     * Replacement for the MySQL 8.4-deprecated SQL_CALC_FOUND_ROWS/FOUND_ROWS()
     * pattern: run a SELECT COUNT(*) query over the same rows.
     *
     * @param string $query e.g. "SELECT COUNT(*) FROM `ss_logs` WHERE ..."
     * @param array $params bound params for the count query (without LIMIT params)
     */
    public function count($query, array $params = []): int
    {
        $statement = $this->statement($query);
        $statement->bindAndExecute($params);

        return (int) $statement->fetchColumn();
    }

    private function shouldEmulatePrepares(): bool
    {
        $value = getenv("DB_EMULATE_PREPARES");

        if ($value === false || $value === "") {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL);
    }
}
