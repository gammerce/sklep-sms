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

        $requiredPlatforms = [$this->settings["sms_service"], $this->settings["transfer_service"]];

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
        if ($newSmsPlatform) {
            $this->db->query(
                $this->db->prepare(
                    "UPDATE `ss_settings` SET `value` = '%d' WHERE `key` = 'sms_service'",
                    [$newSmsPlatform->getId()]
                )
            );
        }

        /** @var PaymentPlatform|null $newTransferPlatform */
        $newTransferPlatform = array_get($paymentPlatforms, $this->settings["transfer_service"]);
        if ($newTransferPlatform) {
            $this->db->query(
                $this->db->prepare(
                    "UPDATE `ss_settings` SET `value` = '%d' WHERE `key` = 'transfer_service'",
                    [$newTransferPlatform->getId()]
                )
            );
        }

        foreach ($this->serverRepository->all() as $server) {
            /** @var PaymentPlatform $smsPlatform */
            $smsPlatform = array_get($paymentPlatforms, $server->getSmsPlatform());
            $this->serverRepository->update(
                $server->getId(),
                $server->getName(),
                $server->getIp(),
                $server->getPort(),
                $smsPlatform ? $smsPlatform->getId() : ''
            );
        }

        // TODO Add server's sms platform foreign key
        // TODO Modify database schema, remove transaction_services
    }
}
