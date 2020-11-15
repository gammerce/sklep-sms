<?php

use App\Install\Migration;
use App\Models\PaymentPlatform;

class MigrateTransactionServices extends Migration
{
    public function up()
    {
        // Make sms_service nullable
        $this->db->query(
            "ALTER TABLE `ss_servers` MODIFY `sms_service` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_bin"
        );

        $smsService = $transferService = "";
        $statement = $this->db->query(
            "SELECT * FROM `ss_settings` WHERE `key` IN ('sms_service', 'transfer_service')"
        );
        foreach ($statement as $row) {
            if ($row["key"] === "sms_service") {
                $smsService = $row["value"];
            }
            if ($row["key"] === "transfer_service") {
                $transferService = $row["value"];
            }
        }

        $paymentPlatforms = [];
        $requiredPlatforms = [$smsService, $transferService];

        $statement = $this->db->query("SELECT * FROM `ss_servers`");
        foreach ($statement as $row) {
            $requiredPlatforms[] = $row["sms_service"];
        }

        $statement = $this->db->query("SELECT * FROM `ss_transaction_services`");
        foreach ($statement as $row) {
            $data = json_decode($row["data"], true);

            $filledData = array_filter(
                $data,
                function ($value, $key) {
                    $keysToCheck = ["account_id", "api", "uid", "service", "key", "email"];
                    return in_array($key, $keysToCheck) && strlen($value);
                },
                ARRAY_FILTER_USE_BOTH
            );

            // Do not migrate ones that are not filled nor required
            if (in_array($row["id"], $requiredPlatforms) || count($filledData)) {
                $this->db
                    ->statement(
                        "INSERT INTO `ss_payment_platforms` " .
                            "SET `name` = ?, `module` = ?, `data` = ?"
                    )
                    ->execute([$row["name"], $row["id"], json_encode($data)]);

                $paymentPlatforms[$row["id"]] = $this->db->lastId();
            }
        }

        /** @var PaymentPlatform|null $newSmsPlatform */
        $newSmsPlatformId = array_get($paymentPlatforms, $smsService, "");
        $this->db
            ->statement("UPDATE `ss_settings` SET `value` = ? WHERE `key` = 'sms_service'")
            ->execute([$newSmsPlatformId]);

        /** @var PaymentPlatform|null $newTransferPlatform */
        $newTransferPlatformId = array_get($paymentPlatforms, $transferService, "");
        $this->db
            ->statement("UPDATE `ss_settings` SET `value` = ? WHERE `key` = 'transfer_service'")
            ->execute([$newTransferPlatformId]);

        $statement = $this->db->query("SELECT * FROM ss_servers");
        foreach ($statement as $row) {
            $smsPlatformId = array_get($paymentPlatforms, $row["sms_service"]);
            $this->db
                ->statement("UPDATE `ss_servers` SET `sms_service` = ? WHERE `id` = ?")
                ->execute([$smsPlatformId, $row["id"]]);
        }

        $this->db->query(
            "UPDATE `ss_settings` SET `key` = 'sms_platform' WHERE `key` = 'sms_service'"
        );
        $this->db->query(
            "UPDATE `ss_settings` SET `key` = 'transfer_platform' WHERE `key` = 'transfer_service'"
        );
        $this->db->query("ALTER TABLE `ss_servers` CHANGE `sms_service` `sms_platform` INT(11)");
        $this->db->query(
            "ALTER TABLE `ss_servers` ADD CONSTRAINT `ss_servers_sms_platform` FOREIGN KEY (`sms_platform`) REFERENCES `ss_payment_platforms` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE"
        );
        try {
            $this->db->query(
                "ALTER TABLE `ss_sms_numbers` DROP FOREIGN KEY `ss_sms_numbers_ibfk_2`"
            );
        } catch (PDOException $e) {
            //
        }
        $this->db->query("DROP TABLE IF EXISTS `ss_transaction_services`");
    }
}
