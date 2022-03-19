<?php

use App\Install\Migration;

class AddPricingPermissions extends Migration
{
    public function up()
    {
        $statement = $this->db->query("SELECT * FROM `ss_groups`");
        foreach ($statement as $row) {
            $permissions = explode(",", $row["permissions"]) ?: [];

            if (in_array("manage_settings", $permissions)) {
                $newPermissions = $row["permissions"] . ",view_pricing,manage_pricing";

                $this->db
                    ->statement("UPDATE `ss_groups` SET `permissions` = ? WHERE `id` = ?")
                    ->execute([$newPermissions, $row["id"]]);
            }
        }
    }
}
