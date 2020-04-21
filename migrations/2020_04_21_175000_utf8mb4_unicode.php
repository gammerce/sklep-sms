<?php

use App\Install\Migration;

class UTF8MB4Unicode extends Migration
{
    public function up()
    {
        $this->executeQueries([
            "ALTER TABLE `ss_user_service_extra_flags` MODIFY `auth_data` VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL",
            "ALTER TABLE `ss_bought_services` MODIFY `auth_data` VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT ''",
            "ALTER TABLE `ss_players_flags` MODIFY `auth_data` VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL",
        ]);
    }
}
