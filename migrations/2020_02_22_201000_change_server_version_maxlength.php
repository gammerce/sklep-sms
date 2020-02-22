<?php

use App\Install\Migration;

class ChangeServerVersionMaxLength extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE `ss_servers` ADD `version` VARCHAR (32)");
    }
}
