<?php

use App\Install\Migration;

class InitShop extends Migration
{
    public function up()
    {
        $this->executeSqlFile("2018_01_14_230341_init_shop.sql");
    }
}
