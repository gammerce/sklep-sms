<?php

use App\Install\Migration;

class AddUserSteamId extends Migration
{
    public function up()
    {
        $queries = ["ALTER TABLE `ss_users` ADD steam_id VARCHAR (32) NOT NULL DEFAULT '';"];
        $this->executeQueries($queries);
    }
}
