<?php

use App\Install\Migration;

class ModifyServiceCodeTable extends Migration
{
    public function up()
    {
        $this->executeQueries([
            "ALTER TABLE `ss_payment_admin` MODIFY `uid` INT(11)",
            "ALTER TABLE `ss_payment_admin` MODIFY `server` INT(11)",
            "ALTER TABLE `ss_payment_admin` DROP COLUMN `amount`",
            "ALTER TABLE `ss_payment_admin` DROP COLUMN `data`",
            "ALTER TABLE `ss_payment_admin` ADD COLUMN `price` INT(11)",
            "UPDATE `ss_payment_admin` SET `uid` = NULL WHERE `uid` = 0",
            "UPDATE `ss_payment_admin` SET `server` = NULL WHERE `server` = 0",
        ]);

        // TODO Migrate tariffs to prices
        // TODO Add foreign keys

        $this->db->query("ALTER TABLE `ss_payment_admin` MODIFY `price` INT(11) NOT NULL");
    }
}
