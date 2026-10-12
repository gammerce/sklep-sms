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
 * Safe on MySQL >= 5.7.7 (innodb_large_prefix + DYNAMIC row format are
 * default there, so widened unique indexes still fit the 3072-byte limit).
 */
class UTF8MB4Conversion extends Migration
{
    public function up()
    {
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
}
