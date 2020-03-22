<?php

use App\Install\Migration;

class AddServerTransferPlatform extends Migration
{
    public function up()
    {
        $this->executeQueries([
            "ALTER TABLE `ss_servers` ADD `transfer_platform` INT(11)",
            "ALTER TABLE `ss_servers` ADD CONSTRAINT `ss_servers_transfer_platform` FOREIGN KEY (`transfer_platform`) REFERENCES `ss_payment_platforms` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE",
        ]);
    }
}
