<?php
namespace Tests\Psr4;

use App\Support\Database;
use App\Install\DatabaseMigration;

class DatabaseSetup
{
    /** @var Database */
    private $db;

    /** @var DatabaseMigration */
    private $databaseMigration;

    public function __construct(Database $db, DatabaseMigration $databaseMigration)
    {
        $this->db = $db;
        $this->databaseMigration = $databaseMigration;
    }

    public function runForTests()
    {
        $this->db->connectWithoutDb();
        $this->db->createDatabaseIfNotExists('sklep_sms_test');
        $this->db->selectDb('sklep_sms_test');
        $this->db->dropAllTables();
        $this->databaseMigration->setup('abc123', 'admin', 'abc123');
    }

    public function run()
    {
        $this->db->connectWithoutDb();
        $this->db->createDatabaseIfNotExists('sklep_sms');
        $this->db->selectDb('sklep_sms');
        $this->db->dropAllTables();
        $this->databaseMigration->setup('abc123', 'admin', 'abc123');
        $this->db->query(
            "INSERT INTO `ss_payment_platforms` (name, module) VALUES ('cssetti', 'cssetti')"
        );
        $this->db->query(
            "INSERT INTO `ss_servers` (name, ip, port) VALUES ('amxx', '172.18.0.5', '27015')"
        );
    }
}
