<?php

use App\Install\Migration;

class UniqueSteamId extends Migration
{
    public function up()
    {
        $this->executeQueries([
            "ALTER TABLE `ss_users` MODIFY `steam_id` VARCHAR (32)",
            "UPDATE `ss_users` SET `steam_id` = NULL WHERE `steam_id` = ''",
        ]);

        try {
            $this->db->query(
                "ALTER TABLE `ss_users` ADD CONSTRAINT `steam_id` UNIQUE (`steam_id`)"
            );
        } catch (PDOException $e) {
            $this->fileLogger->install("Make steam_id UNIQUE error. {$e->getMessage()}");
        }
    }
}
