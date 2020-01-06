<?php

use App\Install\Migration;
use App\Install\MigrationFiles;
use App\Models\PaymentPlatform;
use App\Repositories\PaymentPlatformRepository;
use App\System\Database;

class MigrateTransactionServices extends Migration
{
    /** @var PaymentPlatformRepository */
    private $paymentPlatformRepository;

    public function __construct(
        Database $db,
        MigrationFiles $migrationFiles,
        PaymentPlatformRepository $paymentPlatformRepository
    ) {
        parent::__construct($db, $migrationFiles);
        $this->paymentPlatformRepository = $paymentPlatformRepository;
    }

    public function up()
    {
        // Make sms_service nullable
        $this->db->query(
            "ALTER TABLE `ss_servers` MODIFY `sms_service` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_bin"
        );

        $smsService = $transferService = '';
        $statement = $this->db->query(
            "SELECT * FROM `ss_settings` WHERE `key` IN ('sms_service', 'transfer_service')"
        );
        foreach ($statement as $row) {
            if ($row['key'] === "sms_service") {
                $smsService = $row['value'];
            }
            if ($row['key'] === "transfer_service") {
                $transferService = $row['value'];
            }
        }

        $paymentPlatforms = [];
        $requiredPlatforms = [$smsService, $transferService];

        $statement = $this->db->query("SELECT * FROM ss_servers");
        foreach ($statement as $row) {
            $requiredPlatforms[] = $row['sms_service'];
        }

        $statement = $this->db->query("SELECT * FROM ss_transaction_services");
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
                $paymentPlatform = $this->paymentPlatformRepository->create(
                    $row["name"],
                    $row["id"],
                    $data
                );
                $paymentPlatforms[$paymentPlatform->getModuleId()] = $paymentPlatform;
            }
        }

        /** @var PaymentPlatform|null $newSmsPlatform */
        $newSmsPlatform = array_get($paymentPlatforms, $smsService);
        $newSmsPlatformId = $newSmsPlatform ? $newSmsPlatform->getId() : '';
        $this->db
            ->statement("UPDATE `ss_settings` SET `value` = ? WHERE `key` = 'sms_service'")
            ->execute([$newSmsPlatformId]);

        /** @var PaymentPlatform|null $newTransferPlatform */
        $newTransferPlatform = array_get($paymentPlatforms, $transferService);
        $newTransferPlatformId = $newTransferPlatform ? $newTransferPlatform->getId() : '';
        $this->db
            ->statement("UPDATE `ss_settings` SET `value` = ? WHERE `key` = 'transfer_service'")
            ->execute([$newTransferPlatformId]);

        $statement = $this->db->query("SELECT * FROM ss_servers");
        foreach ($statement as $row) {
            /** @var PaymentPlatform $smsPlatform */
            $smsPlatform = array_get($paymentPlatforms, $row["sms_service"]);
            $smsPlatformId = $smsPlatform ? $smsPlatform->getId() : null;

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
