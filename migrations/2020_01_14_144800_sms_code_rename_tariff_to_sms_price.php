<?php

use App\Install\Migration;

class SmsCodeRenameTariffToSmsPrice extends Migration
{
    public function up()
    {
        $this->executeQueries([
            "UPDATE `ss_sms_codes` SET `tariff` = `tariff` * 100 WHERE `tariff` != 26;",
            "UPDATE `ss_sms_codes` SET `tariff` = 50 WHERE `tariff` = 26;",
            "ALTER TABLE `ss_sms_codes` CHANGE `tariff` `sms_price` INT(11) NOT NULL",
        ]);
    }
}
