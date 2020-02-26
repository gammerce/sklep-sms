<?php

use App\Install\Migration;

class ChangeServerVersionMaxLength extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE `ss_servers` MODIFY `version` VARCHAR (32)");
    }
}
