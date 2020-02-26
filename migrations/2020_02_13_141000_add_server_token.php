<?php

use App\Install\Migration;

class AddServerToken extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE `ss_servers` ADD `token` VARCHAR (32)");
        $this->db->query("ALTER TABLE `ss_servers` ADD CONSTRAINT `token` UNIQUE (`token`)");
    }
}
