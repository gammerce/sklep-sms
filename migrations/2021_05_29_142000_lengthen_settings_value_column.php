<?php

use App\Install\Migration;

class LengthenSettingsValueColumn extends Migration
{
    public function up()
    {
        $this->db->query(
            "ALTER TABLE `ss_settings` MODIFY `value` VARCHAR(512) NOT NULL DEFAULT ''"
        );
    }
}
