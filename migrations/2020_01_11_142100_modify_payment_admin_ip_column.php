<?php

use App\Install\Migration;

class ModifyPaymentAdminIpColumn extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE `ss_payment_admin` MODIFY `ip` VARCHAR(64)");
    }
}
