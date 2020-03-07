<?php

use App\Install\Migration;

class AddDirectBilling extends Migration
{
    public function up()
    {
        $this->db->query(
            "INSERT INTO `ss_settings` SET `key` = 'direct_billing_platform', `value` = ''"
        );
    }
}
