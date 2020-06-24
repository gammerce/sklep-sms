<?php

use App\Install\Migration;

class RemoveAntiSpamQuestions extends Migration
{
    public function up()
    {
        $this->db->query("DROP TABLE `ss_antispam_questions`");
    }
}
