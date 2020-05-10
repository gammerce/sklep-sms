<?php

use App\Install\Migration;

class ReplaceServiceCodeWithPromoCode extends Migration
{
    public function up()
    {
        $this->executeQueries([
            "ALTER TABLE `ss_service_codes` RENAME `ss_promo_codes`",
            "ALTER TABLE `ss_promo_code` ADD COLUMN `quantity_type` VARCHAR(255)",
            "ALTER TABLE `ss_groups` CHANGE COLUMN `view_service_codes` `view_promo_codes` TINYINT(1) NOT NULL DEFAULT '0'",
            "ALTER TABLE `ss_groups` CHANGE COLUMN `manage_service_codes` `manage_promo_codes` TINYINT(1) NOT NULL DEFAULT '0'",
        ]);
    }
}
