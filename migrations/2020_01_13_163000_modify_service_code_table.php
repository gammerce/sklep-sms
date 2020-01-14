<?php

use App\Install\Migration;

class ModifyServiceCodeTable extends Migration
{
    public function up()
    {
        $this->executeQueries([
            "ALTER TABLE `ss_service_codes` MODIFY `uid` INT(11)",
            "ALTER TABLE `ss_service_codes` MODIFY `server` INT(11)",
            "ALTER TABLE `ss_service_codes` DROP COLUMN `amount`",
            "ALTER TABLE `ss_service_codes` DROP COLUMN `data`",
            "ALTER TABLE `ss_service_codes` ADD COLUMN `price` INT(11)",

            "UPDATE `ss_service_codes` SET `uid` = NULL WHERE `uid` = 0",
            "UPDATE `ss_service_codes` SET `server` = NULL WHERE `server` = 0",
        ]);

        // TODO Migrate tariffs into prices

        $this->executeQueries([
            "ALTER TABLE `ss_service_codes` MODIFY `price` INT(11) NOT NULL",
            "ALTER TABLE `ss_service_codes` DROP COLUMN `tariff`",
        ]);

        // TODO Add foreign keys for server, price and uid
    }
}
