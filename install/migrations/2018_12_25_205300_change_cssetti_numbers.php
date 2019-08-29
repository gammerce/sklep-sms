<?php

use Install\Migration;

class ChangeCssettiNumbers extends Migration
{
    public function up()
    {
        $queries = [
            "DELETE FROM `ss_sms_numbers` WHERE `tariff` = 26 AND `service` = 'cssetti';",
            "DELETE FROM `ss_sms_numbers` WHERE `tariff` = 7 AND `service` = 'cssetti';",
            "DELETE FROM `ss_sms_numbers` WHERE `tariff` = 8 AND `service` = 'cssetti';",
            "UPDATE `ss_sms_numbers` SET `number` = '71480' WHERE `tariff` = 1 AND `service` = 'cssetti';",
            "UPDATE `ss_sms_numbers` SET `number` = '72480' WHERE `tariff` = 2 AND `service` = 'cssetti';",
            "UPDATE `ss_sms_numbers` SET `number` = '73480' WHERE `tariff` = 3 AND `service` = 'cssetti';",
            "UPDATE `ss_sms_numbers` SET `number` = '74480' WHERE `tariff` = 4 AND `service` = 'cssetti';",
            "UPDATE `ss_sms_numbers` SET `number` = '75480' WHERE `tariff` = 5 AND `service` = 'cssetti';",
            "UPDATE `ss_sms_numbers` SET `number` = '76480' WHERE `tariff` = 6 AND `service` = 'cssetti';",
            "UPDATE `ss_sms_numbers` SET `number` = '79480' WHERE `tariff` = 9 AND `service` = 'cssetti';",
            "UPDATE `ss_sms_numbers` SET `number` = '91400' WHERE `tariff` = 14 AND `service` = 'cssetti';",
            "UPDATE `ss_sms_numbers` SET `number` = '91900' WHERE `tariff` = 19 AND `service` = 'cssetti';",
            "UPDATE `ss_sms_numbers` SET `number` = '92521' WHERE `tariff` = 25 AND `service` = 'cssetti';",
            "INSERT IGNORE INTO `ss_sms_numbers` SET `number` = '92022', `tariff` = 20, `service` = 'cssetti';",
        ];
        $this->executeQueries($queries);

        $this->changeSmsText();
    }

    private function changeSmsText()
    {
        $result = $this->db->query(
            "SELECT * FROM `ss_transaction_services` WHERE `id` = 'cssetti';"
        );
        $transactionService = $this->db->fetchArrayAssoc($result);

        if (!$transactionService) {
            return;
        }

        $data = json_decode($transactionService["data"], true);
        $data["sms_text"] = "SKLEP";

        $this->db->query(
            $this->db->prepare(
                "UPDATE `ss_transaction_services` SET `data` = '%s' WHERE `id` = 'cssetti';",
                [json_encode($data)]
            )
        );
    }
}
