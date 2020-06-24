<?php

use App\Install\Migration;

class RefactorGroups extends Migration
{
    public function up()
    {
        $privileges = [
            "acp",
            "manage_settings",
            "view_groups",
            "manage_groups",
            "view_player_flags",
            "view_user_services",
            "manage_user_services",
            "view_income",
            "view_users",
            "manage_users",
            "view_sms_codes",
            "manage_sms_codes",
            "view_promo_codes",
            "manage_promo_codes",
            "view_services",
            "manage_services",
            "view_servers",
            "manage_servers",
            "view_logs",
            "manage_logs",
            "update",
        ];

        $this->db->query("ALTER TABLE `ss_groups` ADD COLUMN `permissions` TEXT NOT NULL");

        foreach ($this->db->query("SELECT * FROM `ss_groups`") as $row) {
            $statement = $this->db->statement(
                "UPDATE `ss_groups` SET `permissions` = ? WHERE `id` = ?"
            );

            $permissions = collect($row)
                ->filter(function ($value, $key) use ($privileges) {
                    return in_array($key, $privileges) && $value;
                })
                ->keys()
                ->join(",");

            $statement->execute([$permissions, $row["id"]]);
        }

        foreach ($privileges as $permission) {
            $this->db->query("ALTER TABLE `ss_groups` DROP COLUMN `{$permission}`");
        }
    }
}
