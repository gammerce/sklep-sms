<?php

use App\Install\Migration;

class AddServerLastActiveAt extends Migration
{
    public function up()
    {
        $this->db->query(
            "ALTER TABLE `ss_servers` ADD `last_active_at` TIMESTAMP NULL DEFAULT NULL"
        );
    }
}
