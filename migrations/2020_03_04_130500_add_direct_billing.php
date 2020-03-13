<?php

use App\Install\Migration;

class AddDirectBilling extends Migration
{
    public function up()
    {
        $this->db->query(
            "INSERT INTO `ss_settings` SET `key` = 'direct_billing_platform', `value` = ''"
        );
        $this->db->query(
            "ALTER TABLE `ss_prices` ADD `direct_billing_price`  INT(11) DEFAULT NULL"
        );
    }
}
