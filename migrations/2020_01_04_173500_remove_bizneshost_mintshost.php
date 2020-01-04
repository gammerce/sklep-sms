<?php

use App\Install\Migration;

class RemoveBiznesHostMintsHost extends Migration
{
    public function up()
    {
        $this->executeQueries([
            "DELETE FROM `ss_sms_numbers` WHERE `service` IN ('bizneshost', 'mintshost')"
        ]);
    }
}
