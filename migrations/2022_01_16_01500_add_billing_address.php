<?php

use App\Install\Migration;

class AddUserBillingAddress extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE `ss_users` ADD `billing_address` TEXT NOT NULL");
        $this->db->query(
            "ALTER TABLE `ss_bought_services` ADD `invoice_id` VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin"
        );
    }
}
