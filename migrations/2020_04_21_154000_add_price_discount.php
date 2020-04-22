<?php

use App\Install\Migration;

class AddPriceDiscount extends Migration
{
    public function up()
    {
        $this->executeQueries(["ALTER TABLE `ss_prices` ADD `discount` INT(11) DEFAULT NULL"]);
    }
}
