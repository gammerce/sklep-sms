<?php

use App\Install\Migration;

/**
 * Convert all tables to utf8mb4.
 *
 * Fresh installs still create most tables as utf8 (see init.sql), with later
 * migrations moving individual columns to utf8mb4. MySQL 8.4 deprecates the
 * utf8mb3 alias, so normalize everything to utf8mb4 now.
 *
 * Two passes: first CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_bin
 * for everything, then restore utf8mb4_unicode_ci on the columns that were
 * case-insensitive before (logins, customer-entered codes, names and other
 * lookups — the pre-migration production behavior, verified against the
 * previous database). Everything *_bin there stays
 * *_bin here (tokens, hashes and other byte-exact paths are untouched).
 * Tables are enumerated dynamically so this also covers custom (License)
 * tables.
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
    const CI_COLLATION = "utf8mb4_unicode_ci";

    /**
     * @return string[][] [table, column] pairs that were *_ci before
     * (previous production database, verified 2026-10-13).
     */
    private function ciColumns(): array
    {
        return [
            ["ss_bought_services", "amount"],
            ["ss_bought_services", "email"],
            ["ss_bought_services", "extra_data"],
            ["ss_logs", "text"],
            ["ss_migrations", "name"],
            ["ss_payment_admin", "platform"],
            ["ss_payment_code", "code"],
            ["ss_payment_code", "ip"],
            ["ss_payment_code", "platform"],
            ["ss_payment_direct_billing", "external_id"],
            ["ss_payment_direct_billing", "ip"],
            ["ss_payment_direct_billing", "platform"],
            ["ss_payment_platforms", "name"],
            ["ss_payment_platforms", "module"],
            ["ss_payment_platforms", "data"],
            ["ss_payment_sms", "code"],
            ["ss_payment_sms", "text"],
            ["ss_payment_sms", "number"],
            ["ss_payment_sms", "platform"],
            ["ss_payment_transfer", "platform"],
            ["ss_payment_wallet", "platform"],
            ["ss_promo_codes", "code"],
            ["ss_promo_codes", "quantity_type"],
            ["ss_servers", "name"],
            ["ss_servers", "ip"],
            ["ss_servers", "port"],
            ["ss_servers", "type"],
            ["ss_services", "name"],
            ["ss_services", "short_description"],
            ["ss_services", "description"],
            ["ss_services", "tag"],
            ["ss_services", "module"],
            ["ss_services", "groups"],
            ["ss_services", "flags"],
            ["ss_services", "data"],
            ["ss_sms_codes", "code"],
            ["ss_user_service_shopsms_license", "service_id"],
            ["ss_user_service_shopsms_license", "identifier"],
            ["ss_user_service_shopsms_license", "email"],
            ["ss_user_service_shopsms_license", "subdomain"],
            ["ss_users", "username"],
            ["ss_users", "password"],
            ["ss_users", "email"],
            ["ss_users", "forename"],
            ["ss_users", "surname"],
            ["ss_users", "reset_password_key"],
        ];
    }

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

            $this->restoreCiCollations();
        } finally {
            $this->db->enableForeignKeyConstraints();
        }
    }

    private function restoreCiCollations(): void
    {
        $failures = [];

        foreach ($this->ciColumns() as [$table, $column]) {
            try {
                $this->restoreColumn($table, $column);
            } catch (Throwable $e) {
                $failures[] = "{$table}.{$column}: {$e->getMessage()}";
            }
        }

        if ($failures !== []) {
            throw new RuntimeException(
                "ci-collation restore incomplete: " . implode("; ", $failures)
            );
        }
    }

    private function restoreColumn(string $table, string $column): void
    {
        $current = $this->db
            ->query(
                "SELECT COLLATION_NAME FROM information_schema.COLUMNS " .
                    "WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$table}' " .
                    "AND COLUMN_NAME = '{$column}'"
            )
            ->fetchColumn();

        if ($current === false || $current === self::CI_COLLATION) {
            return;
        }

        $definition = $this->db
            ->query(
                "SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT " .
                    "FROM information_schema.COLUMNS " .
                    "WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$table}' " .
                    "AND COLUMN_NAME = '{$column}'"
            )
            ->fetch(PDO::FETCH_ASSOC);

        if (!is_array($definition) || !isset($definition["COLUMN_TYPE"])) {
            return;
        }

        $sql =
            "ALTER TABLE `{$table}` MODIFY `{$column}` {$definition["COLUMN_TYPE"]} " .
            "CHARACTER SET utf8mb4 COLLATE " .
            self::CI_COLLATION;

        if ($definition["IS_NULLABLE"] === "NO") {
            $sql .= " NOT NULL";
        }

        if ($definition["COLUMN_DEFAULT"] !== null) {
            $sql .= " DEFAULT '" . str_replace("'", "''", $definition["COLUMN_DEFAULT"]) . "'";
        }

        $this->db->query($sql);
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
            $dynamic = in_array(
                $rowFormats[$index["TABLE_NAME"]] ?? "",
                ["Dynamic", "Compressed"],
                true
            );
            $limit = $modernDefaults || ($largePrefix && $dynamic) ? 3072 : 767;
            if ((int) $index["bytes"] > $limit - 64) {
                $offenders[] = "`{$index["TABLE_NAME"]}`.`{$index["INDEX_NAME"]}` (~{$index["bytes"]} bytes, limit {$limit})";
            }
        }

        if ($offenders !== []) {
            throw new RuntimeException(
                "utf8mb4 conversion aborted: these indexes would exceed this server's index limit " .
                    "(MySQL {$version}): " .
                    implode(", ", $offenders) .
                    ". " .
                    "Fix: ALTER TABLE <table> ROW_FORMAT=DYNAMIC for the listed tables " .
                    "and SET GLOBAL innodb_large_prefix=ON (persist in my.cnf), then re-run. " .
                    "Nothing was converted."
            );
        }
    }
}
