<?php

use App\Install\Migration;

class AddSmsCodeExpires extends Migration
{
    public function up()
    {
        $this->db->query(
            "ALTER TABLE `ss_sms_codes` ADD `expires_at` TIMESTAMP"
        );
    }
}
