<?php

use App\Install\Migration;

class AddPaymentTransferFreeColumn extends Migration
{
    public function up()
    {
        $this->db->query(
            "ALTER TABLE `ss_payment_transfer` ADD `free` TINYINT(1) NOT NULL DEFAULT '0'"
        );
    }
}
