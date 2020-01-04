<?php

use App\Install\Migration;
use App\Install\MigrationFiles;
use App\Models\PaymentPlatform;
use App\Repositories\PaymentPlatformRepository;
use App\Repositories\ServerRepository;
use App\System\Database;
use App\System\Settings;

class MigrateTransactionServices extends Migration
{
    /** @var PaymentPlatformRepository */
    private $paymentPlatformRepository;

    /** @var ServerRepository */
    private $serverRepository;

    /** @var Settings */
    private $settings;

    public function __construct(
        Database $db,
        MigrationFiles $migrationFiles,
        PaymentPlatformRepository $paymentPlatformRepository,
        ServerRepository $serverRepository,
        Settings $settings
    ) {
        parent::__construct($db, $migrationFiles);
        $this->paymentPlatformRepository = $paymentPlatformRepository;
        $this->serverRepository = $serverRepository;
        $this->settings = $settings;
    }

    public function up()
    {
        $paymentPlatforms = [];
        $requiredPlatforms = [
            $this->settings["sms_platform"],
            $this->settings["transfer_platform"],
        ];

        foreach ($this->serverRepository->all() as $server) {
            $requiredPlatforms[] = $server->getSmsPlatform();
        }

        $result = $this->db->query("SELECT * FROM ss_transaction_services");

        while ($row = $this->db->fetchArrayAssoc($result)) {
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
            $paymentPlatforms[$paymentPlatform->getModule()] = $paymentPlatform;
        }

        /** @var PaymentPlatform|null $newSmsPlatform */
        $newSmsPlatform = array_get($paymentPlatforms, $this->settings["sms_service"]);
        $newSmsPlatformId = $newSmsPlatform ? $newSmsPlatform->getId() : '';
        $this->db->query(
            $this->db->prepare(
                "UPDATE `ss_settings` SET `value` = '%d' WHERE `key` = 'sms_service'",
                [$newSmsPlatformId]
            )
        );

        /** @var PaymentPlatform|null $newTransferPlatform */
        $newTransferPlatform = array_get($paymentPlatforms, $this->settings["transfer_service"]);
        $newTransferPlatformId = $newTransferPlatform ? $newTransferPlatform->getId() : '';
        $this->db->query(
            $this->db->prepare(
                "UPDATE `ss_settings` SET `value` = '%d' WHERE `key` = 'transfer_service'",
                [$newTransferPlatformId]
            )
        );

        $result = $this->db->query("SELECT * FROM ss_servers");
        while ($row = $this->db->fetchArrayAssoc($result)) {
            /** @var PaymentPlatform $smsPlatform */
            $smsPlatform = array_get($paymentPlatforms, $row["sms_service"]);
            $smsPlatformId = $smsPlatform ? $smsPlatform->getId() : null;

            $this->db
                ->statement("UPDATE `ss_servers` SET `sms_service` = ? WHERE `id` = ?")
                ->execute([$smsPlatformId, $row["id"]]);
        }

        $this->executeQueries([
            "UPDATE `ss_settings` SET `key` = 'sms_platform' WHERE `key` = 'sms_service'",
            "UPDATE `ss_settings` SET `key` = 'transfer_platform' WHERE `key` = 'transfer_service'",
        ]);

        try {
            $this->db->query(
                "ALTER TABLE `ss_servers` CHANGE `sms_service` `sms_platform` INT(11)"
            );
        } catch (PDOException $e) {
            //
        }

        try {
            $this->db->query(
                "ALTER TABLE `ss_servers` ADD CONSTRAINT `ss_servers_sms_platform` FOREIGN KEY (`sms_platform`) REFERENCES `ss_payment_platforms` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE"
            );
        } catch (PDOException $e) {
            //
        }

        try {
            $this->db->query(
                "ALTER TABLE `ss_sms_numbers` DROP FOREIGN KEY IF EXISTS `ss_sms_numbers_ibfk_2`"
            );
        } catch (PDOException $e) {
            //
        }

        $this->db->query(
            "DROP TABLE IF EXISTS `ss_transaction_services`"
        );

        // TODO Use data fields from transaction_services
        // TODO Do not allow default payment platform if not set
    }
}
