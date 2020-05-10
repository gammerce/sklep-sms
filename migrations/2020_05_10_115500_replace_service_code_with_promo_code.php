<?php

use App\Install\Migration;

class ReplaceServiceCodeWithPromoCode extends Migration
{
    public function up()
    {
        $this->executeQueries([
            "ALTER TABLE `ss_service_codes` RENAME `ss_promo_codes`",
            "ALTER TABLE `ss_promo_code` ADD COLUMN `quantity_type` VARCHAR(255)",
        ]);
    }
}
