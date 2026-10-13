<?php

use App\Install\Migration;

class WidenSettingsValue extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE `ss_settings` MODIFY `value` TEXT NOT NULL");
    }
}
