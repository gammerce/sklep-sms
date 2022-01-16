<?php

use App\Install\Migration;

class AddUserBillingAddress extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE `ss_users` ADD `billing_address` TEXT NOT NULL");
    }
}
