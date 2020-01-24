<?php

use App\Install\Migration;

class AuthDataUTF8MB4Compliant extends Migration
{
    public function up()
    {
        $this->executeQueries([
            "ALTER TABLE `ss_user_service_extra_flags` MODIFY `auth_data` VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_polish_ci NOT NULL",
            "ALTER TABLE `ss_bought_services` MODIFY `auth_data` VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_polish_ci DEFAULT ''",
            "ALTER TABLE `ss_players_flags` MODIFY `auth_data` VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_polish_ci NOT NULL",
        ]);
    }
}
