<?php

use App\Install\Migration;

class ReplaceServiceCodeWithPromoCode extends Migration
{
    public function up()
    {
        $this->executeQueries([
            "UPDATE `ss_service_codes` SET `quantity` = 100",
            "ALTER TABLE `ss_service_codes` RENAME `ss_promo_codes`",
            "ALTER TABLE `ss_promo_codes` ADD COLUMN `quantity_type` VARCHAR(255) NOT NULL",
            "ALTER TABLE `ss_promo_codes` CHANGE COLUMN `quantity` `quantity` INT(11) NOT NULL",
            "ALTER TABLE `ss_promo_codes` CHANGE COLUMN `timestamp` `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "ALTER TABLE `ss_promo_codes` DROP FOREIGN KEY `ss_service_codes_server_fk`",
            "ALTER TABLE `ss_promo_codes` DROP FOREIGN KEY `ss_service_codes_user_fk`",
            "ALTER TABLE `ss_promo_codes` CHANGE COLUMN `server` `server_id` INT(11)",
            "ALTER TABLE `ss_promo_codes` CHANGE COLUMN `uid` `user_id` INT(11)",
            "ALTER TABLE `ss_promo_codes` ADD COLUMN `expires_at` TIMESTAMP NULL DEFAULT NULL",
            "ALTER TABLE `ss_promo_codes` ADD COLUMN `usage_limit` INT(11)",
            "ALTER TABLE `ss_promo_codes` ADD COLUMN `usage_count` INT(11) NOT NULL DEFAULT 0",
            "ALTER TABLE `ss_groups` CHANGE COLUMN `view_service_codes` `view_promo_codes` TINYINT(1) NOT NULL DEFAULT '0'",
            "ALTER TABLE `ss_groups` CHANGE COLUMN `manage_service_codes` `manage_promo_codes` TINYINT(1) NOT NULL DEFAULT '0'",
        ]);

        foreach ($this->db->query("SELECT * FROM `ss_service_codes`") as $serviceCode) {
            $this->db
                ->statement(
                    "UPDATE `ss_service_codes` SET `quantity_type` = 'percentage', `server_id` = ?, `user_id` = ? WHERE `id` = ?"
                )
                ->execute([
                    $serviceCode["server"] ?: null,
                    $serviceCode["user_id"] ?: null,
                    $serviceCode["id"],
                ]);
        }

        $this->db->query(
            <<<EOF
ALTER TABLE `ss_promo_codes`
  ADD CONSTRAINT `ss_promo_codes_server_fk` FOREIGN KEY (`server_id`) REFERENCES `ss_servers` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE,
  ADD CONSTRAINT `ss_promo_codes_user_fk` FOREIGN KEY (`user_id`) REFERENCES `ss_users` (`uid`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;
EOF
        );
    }
}
