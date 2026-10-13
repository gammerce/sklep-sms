<?php

use App\Install\Migration;

/**
 * Restore case-insensitive collations wiped by the blanket utf8mb4
 * conversion (which normalized everything to utf8mb4_bin).
 *
 * Bear ran these columns case-insensitive (*_ci): logins, payment / SMS /
 * promo codes customers type in, server names/IPs, service names and
 * descriptions, bought-service emails, license-service identifiers, user
 * names. Everything *_bin there stays *_bin here (tokens, hashes and other
 * byte-exact paths are untouched).
 *
 * Self-contained and idempotent: column types, nullability and defaults are
 * read from the live schema (never assumed), columns already correct are
 * skipped, and per-column failures are collected and reported together so
 * one problem (e.g. a case-duplicate under a UNIQUE index) cannot block
 * the rest. Safe on MySQL 5.7 through 8.4 (plain ALTER + information_schema
 * only); byte widths are unchanged so index limits are unaffected.
 */
class RestoreCiCollations extends Migration
{
    const TARGET_COLLATION = "utf8mb4_unicode_ci";

    /**
     * @return string[][] [table, column] pairs that were *_ci on bear
     * (live-verified 2026-10-13).
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

        if ($current === false || $current === self::TARGET_COLLATION) {
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
            throw new RuntimeException("column not found");
        }

        $sql =
            "ALTER TABLE `{$table}` MODIFY `{$column}` {$definition["COLUMN_TYPE"]} " .
            "CHARACTER SET utf8mb4 COLLATE " .
            self::TARGET_COLLATION;

        if ($definition["IS_NULLABLE"] === "NO") {
            $sql .= " NOT NULL";
        }

        if ($definition["COLUMN_DEFAULT"] !== null) {
            $sql .= " DEFAULT '" . str_replace("'", "''", $definition["COLUMN_DEFAULT"]) . "'";
        }

        $this->db->query($sql);
    }
}
