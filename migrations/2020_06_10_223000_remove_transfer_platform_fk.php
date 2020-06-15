<?php

use App\Install\Migration;

class RemoveTransferPlatformFk extends Migration
{
    public function up()
    {
        $this->executeQueries([
            "ALTER TABLE `ss_servers` DROP FOREIGN KEY `ss_servers_transfer_platform`",
            "ALTER TABLE `ss_servers` MODIFY `transfer_platform` VARCHAR(255)",
            "UPDATE `ss_servers` SET `transfer_platform` = '' WHERE `transfer_platform` IS NULL",
            "ALTER TABLE `ss_servers` MODIFY `transfer_platform` VARCHAR(255) NOT NULL",
            "ALTER TABLE `ss_payment_transfer` MODIFY `id` VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL",
            "ALTER TABLE `ss_bought_services` MODIFY `payment_id` VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL",
            "ALTER TABLE `ss_payment_transfer` ADD `cost` INT(11) NOT NULL",
            "UPDATE `ss_payment_transfer` SET `cost` = `income`",
        ]);
    }
}
