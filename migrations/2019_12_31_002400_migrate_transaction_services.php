<?php

use App\Install\Migration;
use App\Install\MigrationFiles;
use App\Models\PaymentPlatform;
use App\Repositories\PaymentPlatformRepository;
use App\System\Database;
use App\System\Settings;

class MigrateTransactionServices extends Migration
{
    /** @var PaymentPlatformRepository */
    private $paymentPlatformRepository;

    /** @var Settings */
    private $settings;

    public function __construct(
        Database $db,
        MigrationFiles $migrationFiles,
        PaymentPlatformRepository $paymentPlatformRepository,
        Settings $settings
    ) {
        parent::__construct($db, $migrationFiles);
        $this->paymentPlatformRepository = $paymentPlatformRepository;
        $this->settings = $settings;
    }

    public function up()
    {
        $this->db->query(
            "ALTER TABLE `ss_servers` MODIFY `sms_service` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_bin"
        );

        $paymentPlatforms = [];
        $requiredPlatforms = [$this->settings["sms_service"], $this->settings["transfer_service"]];

        $statement = $this->db->query("SELECT * FROM ss_servers");
        foreach ($statement as $row) {
            $requiredPlatforms[] = $row['sms_service'];
        }

        $statement = $this->db->query("SELECT * FROM ss_transaction_services");
        foreach ($statement as $row) {
            $data = json_decode($row["data"], true);

            // Do not migrate ones that are not filled
            if (!in_array($row["id"], $requiredPlatforms)) {
                $keysToCheck = ["account_id", "sms_text", "api", "uid", "service", "key", "email"];

                foreach ($keysToCheck as $key) {
                    if (array_key_exists($key, $data) && !strlen($data[$key])) {
                        continue;
                    }
                }
            }

            $paymentPlatform = $this->paymentPlatformRepository->create(
                $row["name"],
                $row["id"],
                $data
            );
            $paymentPlatforms[$paymentPlatform->getModuleId()] = $paymentPlatform;
        }

        /** @var PaymentPlatform|null $newSmsPlatform */
        $newSmsPlatform = array_get($paymentPlatforms, $this->settings["sms_service"]);
        $newSmsPlatformId = $newSmsPlatform ? $newSmsPlatform->getId() : '';
        $this->db
            ->statement("UPDATE `ss_settings` SET `value` = ? WHERE `key` = 'sms_service'")
            ->execute([$newSmsPlatformId]);

        /** @var PaymentPlatform|null $newTransferPlatform */
        $newTransferPlatform = array_get($paymentPlatforms, $this->settings["transfer_service"]);
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
