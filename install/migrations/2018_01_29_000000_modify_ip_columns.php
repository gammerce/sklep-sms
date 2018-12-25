<?php

use Install\Migration;

class ModifyIpColumns extends Migration
{
    public function up()
    {
        $queries = [
            "ALTER TABLE `ss_users` MODIFY `lastip` VARCHAR(64);",
            "ALTER TABLE `ss_users` MODIFY `regip` VARCHAR(64);",
            "ALTER TABLE `ss_payment_code` MODIFY `ip` VARCHAR(64);",
            "ALTER TABLE `ss_payment_sms` MODIFY `ip` VARCHAR(64);",
            "ALTER TABLE `ss_payment_transfer` MODIFY `ip` VARCHAR(64);",
            "ALTER TABLE `ss_payment_wallet` MODIFY `ip` VARCHAR(64);",
        ];

        $this->executeQueries($queries);
    }
}
