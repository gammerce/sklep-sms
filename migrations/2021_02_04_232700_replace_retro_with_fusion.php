<?php

use App\Install\Migration;

class ReplaceRetroWithFusion extends Migration
{
    public function up()
    {
        $this->db->query(
            "UPDATE `ss_settings` SET `value` = 'fusion' WHERE `key` = 'theme' AND `value` = 'retro'"
        );
    }
}
