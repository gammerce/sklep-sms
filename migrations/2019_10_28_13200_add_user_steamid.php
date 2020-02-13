<?php

use App\Install\Migration;

class AddUserSteamId extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE `ss_users` ADD `steam_id` VARCHAR (32)");
    }
}
