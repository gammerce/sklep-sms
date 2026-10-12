<?php

use App\Install\Migration;

/**
 * Convert all tables to utf8mb4.
 *
 * Fresh installs still create most tables as utf8 (see init.sql), with later
 * migrations moving individual columns to utf8mb4. MySQL 8.4 deprecates the
 * utf8mb3 alias, so normalize everything to utf8mb4 now.
 *
 * utf8mb4_bin is used deliberately: it preserves the exact comparison
 * semantics of the previous utf8/utf8_bin columns (binary, case-sensitive),
 * unlike utf8mb4_unicode_ci which would silently change lookups. Tables are
 * enumerated dynamically so this also covers custom (License) tables.
 *
 * Supported servers: MySQL 5.7 through 8.4 (and MariaDB 10.2+). A widened
 * utf8mb4 index needs at most 3072 bytes on modern defaults, but only 767
 * on legacy configs (MySQL 5.7 with innodb_large_prefix=OFF or non-DYNAMIC
 * row format, e.g. tables created years ago on 5.5/5.6). The pre-flight check
 * below aborts BEFORE converting anything when an index would not fit,
 * instead of dying mid-migration (DDL commits per statement, so a late
 * failure would leave a half-converted, mixed-collation schema behind).
 */
class UTF8MB4Conversion extends Migration
{
    public function up()
    {
        $this->assertIndexesFitAfterConversion();

        $tables = $this->db
            ->query(
                "SELECT TABLE_NAME FROM information_schema.TABLES " .
                    "WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'"
            )
            ->fetchAll(PDO::FETCH_COLUMN);

        $this->db->disableForeignKeyConstraints();

        try {
            foreach ($tables as $table) {
                $this->db->query(
                    "ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_bin"
                );
            }

            $this->db->query("ALTER DATABASE CHARACTER SET utf8mb4 COLLATE utf8mb4_bin");
        } finally {
            $this->db->enableForeignKeyConstraints();
        }
    }

    private function assertIndexesFitAfterConversion(): void
    {
        $version = (string) $this->db->query("SELECT VERSION()")->fetchColumn();
        $isMariaDb = stripos($version, "mariadb") !== false;
        // MariaDB 10.x compares >= 8.0 numerically but behaves like 5.7 here.
        $modernDefaults = !$isMariaDb && version_compare($version, "8.0.0", ">=");

        $largePrefix = true;
        if (!$modernDefaults) {
            try {
                $largePrefix = $this->db->query("SELECT @@innodb_large_prefix")->fetchColumn() == 1;
            } catch (Throwable $e) {
                $largePrefix = false;
            }
        }

        $rowFormats = $this->db
            ->query(
                "SELECT TABLE_NAME, ROW_FORMAT FROM information_schema.TABLES " .
                    "WHERE TABLE_SCHEMA = DATABASE()"
            )
            ->fetchAll(PDO::FETCH_KEY_PAIR);

        // Byte size each string index would have as utf8mb4 (4 bytes/char).
        // Only columns that actually grow (not yet utf8mb4); binary types are
        // untouched by CONVERT TO CHARACTER SET and excluded.
        $indexes = $this->db
            ->query(
                "SELECT s.TABLE_NAME, s.INDEX_NAME, " .
                    "SUM(IF(s.SUB_PART IS NULL, ch.CHARACTER_MAXIMUM_LENGTH, " .
                    "LEAST(s.SUB_PART, ch.CHARACTER_MAXIMUM_LENGTH)) * 4) AS bytes " .
                    "FROM information_schema.STATISTICS s " .
                    "JOIN information_schema.COLUMNS ch " .
                    "ON ch.TABLE_SCHEMA = s.TABLE_SCHEMA " .
                    "AND ch.TABLE_NAME = s.TABLE_NAME " .
                    "AND ch.COLUMN_NAME = s.COLUMN_NAME " .
                    "WHERE s.TABLE_SCHEMA = DATABASE() " .
                    "AND s.INDEX_TYPE != 'FULLTEXT' " .
                    "AND ch.DATA_TYPE IN ('char', 'varchar', 'text', 'tinytext', 'mediumtext', 'longtext') " .
                    "AND ch.CHARACTER_SET_NAME != 'utf8mb4' " .
                    "GROUP BY s.TABLE_NAME, s.INDEX_NAME"
            )
            ->fetchAll(PDO::FETCH_ASSOC);

        $offenders = [];
        foreach ($indexes as $index) {
            $dynamic = in_array($rowFormats[$index["TABLE_NAME"]] ?? "", ["Dynamic", "Compressed"], true);
            $limit = ($modernDefaults || ($largePrefix && $dynamic)) ? 3072 : 767;
            if ((int) $index["bytes"] > $limit - 64) {
                $offenders[] = "`{$index["TABLE_NAME"]}`.`{$index["INDEX_NAME"]}` (~{$index["bytes"]} bytes, limit {$limit})";
            }
        }

        if ($offenders !== []) {
            throw new RuntimeException(
                "utf8mb4 conversion aborted: these indexes would exceed this server's index limit " .
                    "(MySQL {$version}): " . implode(", ", $offenders) . ". " .
                    "Fix: ALTER TABLE <table> ROW_FORMAT=DYNAMIC for the listed tables " .
                    "and SET GLOBAL innodb_large_prefix=ON (persist in my.cnf), then re-run. " .
                    "Nothing was converted."
            );
        }
    }
}
