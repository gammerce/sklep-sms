<?php

use Install\Migration;

class ChangeMicrosmsNumbers extends Migration
{
    public function up()
    {
        $queries = [
            "DELETE FROM `ss_sms_numbers` WHERE `tariff` = 26 AND `service` = 'microsms';",
            "DELETE FROM `ss_sms_numbers` WHERE `tariff` = 7 AND `service` = 'microsms';",
            "DELETE FROM `ss_sms_numbers` WHERE `tariff` = 8 AND `service` = 'microsms';",
            "DELETE FROM `ss_sms_numbers` WHERE `tariff` = 10 AND `service` = 'microsms';",
            "DELETE FROM `ss_sms_numbers` WHERE `tariff` = 11 AND `service` = 'microsms';",
            "DELETE FROM `ss_sms_numbers` WHERE `tariff` = 16 AND `service` = 'microsms';",
            "UPDATE `ss_sms_numbers` SET `number` = '71480' WHERE `tariff` = 1 AND `service` = 'microsms';",
            "UPDATE `ss_sms_numbers` SET `number` = '72480' WHERE `tariff` = 2 AND `service` = 'microsms';",
            "UPDATE `ss_sms_numbers` SET `number` = '73480' WHERE `tariff` = 3 AND `service` = 'microsms';",
            "UPDATE `ss_sms_numbers` SET `number` = '74480' WHERE `tariff` = 4 AND `service` = 'microsms';",
            "UPDATE `ss_sms_numbers` SET `number` = '75480' WHERE `tariff` = 5 AND `service` = 'microsms';",
            "UPDATE `ss_sms_numbers` SET `number` = '76480' WHERE `tariff` = 6 AND `service` = 'microsms';",
            "UPDATE `ss_sms_numbers` SET `number` = '79480' WHERE `tariff` = 9 AND `service` = 'microsms';",
            "UPDATE `ss_sms_numbers` SET `number` = '91400' WHERE `tariff` = 14 AND `service` = 'microsms';",
            "UPDATE `ss_sms_numbers` SET `number` = '91900' WHERE `tariff` = 19 AND `service` = 'microsms';",
            "UPDATE `ss_sms_numbers` SET `number` = '92022' WHERE `tariff` = 20 AND `service` = 'microsms';",
            "UPDATE `ss_sms_numbers` SET `number` = '92521' WHERE `tariff` = 25 AND `service` = 'microsms';",
        ];
        $this->executeQueries($queries);
    }
}
