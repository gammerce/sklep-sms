<?php

use App\Install\Migration;

class ReplaceServiceCodeWithPromoCode extends Migration
{
    public function up()
    {
        // TODO Handle existing service codes
        // server 0 === null
        // uid 0 === null
        // service 0 === null

        $this->executeQueries([
            "ALTER TABLE `ss_service_codes` RENAME `ss_promo_codes`",
            "ALTER TABLE `ss_promo_code` ADD COLUMN `quantity_type` VARCHAR(255) NOT NULL",
            "ALTER TABLE `ss_promo_code` CHANGE COLUMN `quantity` `quantity` INT(11) NOT NULL",
            "ALTER TABLE `ss_promo_code` CHANGE COLUMN `timestamp` `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "ALTER TABLE `ss_promo_code` CHANGE COLUMN `service` `service_id` VARCHAR(16)",
            "ALTER TABLE `ss_promo_code` CHANGE COLUMN `server` `server_id` INT(11)",
            "ALTER TABLE `ss_promo_code` CHANGE COLUMN `uid` `user_id` INT(11)",
            "ALTER TABLE `ss_promo_code` ADD COLUMN `expires_at` TIMESTAMP",
            "ALTER TABLE `ss_promo_code` ADD COLUMN `usage_limit` INT(11)",
            "ALTER TABLE `ss_promo_code` ADD COLUMN `usage_count` INT(11) NOT NULL DEFAULT 0",
            "ALTER TABLE `ss_groups` CHANGE COLUMN `view_service_codes` `view_promo_codes` TINYINT(1) NOT NULL DEFAULT '0'",
            "ALTER TABLE `ss_groups` CHANGE COLUMN `manage_service_codes` `manage_promo_codes` TINYINT(1) NOT NULL DEFAULT '0'",
        ]);
    }
}
