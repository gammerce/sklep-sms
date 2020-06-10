<?php

use App\Install\Migration;

class RemoveTransferPlatformFk extends Migration
{
    public function up()
    {
        $this->db->query(
            "ALTER TABLE `ss_servers` DROP FOREIGN KEY `ss_servers_transfer_platform`"
        );
    }
}
