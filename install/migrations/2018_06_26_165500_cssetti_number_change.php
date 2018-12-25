<?php

use Install\Migration;

class CssettiNumberChange extends Migration
{
    public function up()
    {
        $this->db->query(
            "UPDATE `ss_sms_numbers` SET `number` = '7155' WHERE `tariff` = 1 AND `service` = 'cssetti';"
        );
    }
}
