<?php

use App\Install\Migration;

class Init extends Migration
{
    public function up()
    {
        $this->executeSqlFile("2018_01_14_230340_init.sql");
    }
}
