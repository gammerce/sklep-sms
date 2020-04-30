<?php

use App\Install\Migration;

class ReplaceDefaultWithRetro extends Migration
{
    public function up()
    {
        $this->db->query(
            "UPDATE `ss_settings` SET `value` = 'retro' WHERE `key` = 'theme' AND `value` = 'default'"
        );
    }
}
