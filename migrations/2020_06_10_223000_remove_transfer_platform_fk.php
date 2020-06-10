<?php

use App\Install\Migration;

class RemoveTransferPlatformFk extends Migration
{
    public function up()
    {
        $this->executeQueries([
            "ALTER TABLE `ss_servers` DROP FOREIGN KEY `ss_servers_transfer_platform`",
            "ALTER TABLE `ss_servers` MODIFY `transfer_platform` VARCHAR(255) NOT NULL",
        ]);
    }
}
