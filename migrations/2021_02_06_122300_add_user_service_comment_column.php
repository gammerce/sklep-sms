<?php

use App\Install\Migration;

class AddUserServiceCommentColumn extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE `ss_user_service` ADD `comment` TEXT NOT NULL");
    }
}
