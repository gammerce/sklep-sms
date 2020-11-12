<?php

use App\Install\Migration;

class PriceTable extends Migration
{
    public function up()
    {
        $this->db->query("DROP TABLE IF EXISTS `ss_sms_numbers`");

        $this->executeQueries([
            "DROP TABLE IF EXISTS `ss_prices`",
            <<<EOF
CREATE TABLE IF NOT EXISTS `ss_prices` (
  `id`              INT(11)     NOT NULL AUTO_INCREMENT,
  `service`         VARCHAR(16) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `server`          INT(11) DEFAULT NULL,
  `sms_price`       INT(11) DEFAULT NULL,
  `transfer_price`  INT(11) DEFAULT NULL,
  `quantity`        INT(11),
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `service_server_sms_quantity` (`service`, `server`, `sms_price`, `quantity`),
  UNIQUE KEY `service_server_transfer_quantity` (`service`, `server`, `transfer_price`, `quantity`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;
EOF
        ,
        ]);

        $this->db->query(
            <<<EOF
ALTER TABLE `ss_prices`
  ADD CONSTRAINT `ss_prices_service_fk` FOREIGN KEY (`service`) REFERENCES `ss_services` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE,
  ADD CONSTRAINT `ss_prices_server_fk` FOREIGN KEY (`server`) REFERENCES `ss_servers` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;
EOF
        );

        $tariffProvision = [];
        foreach ($this->db->query("SELECT * FROM ss_tariffs") as $row) {
            $tariffProvision[$row["id"]] = $row["provision"];
        }

        $tariffPrice = [];
        foreach ($this->db->query("SELECT * FROM ss_pricelist") as $row) {
            try {
                $this->db
                    ->statement(
                        <<<EOF
INSERT INTO `ss_prices` (`service`, `server`, `sms_price`, `transfer_price`, `quantity`)
VALUES (?, ?, ?, ?, ?)
EOF
                    )
                    ->execute([
                        $row["service"],
                        $row["server"] === -1 ? null : $row["server"],
                        $this->tariffToSmsPrice($row["tariff"]),
                        array_get($tariffProvision, $row["tariff"]),
                        $row["amount"] === -1 ? null : $row["amount"],
                    ]);

                $tariffPrice[$row["tariff"]] = $this->db->lastId();
            } catch (PDOException $e) {
                $this->fileLogger->install("Migrate pricelist error. {$e->getMessage()}");
            }
        }

        //
        // SERVICE CODES
        //

        $this->executeQueries([
            "ALTER TABLE `ss_service_codes` MODIFY `uid` INT(11)",
            "ALTER TABLE `ss_service_codes` MODIFY `server` INT(11)",
            "ALTER TABLE `ss_service_codes` DROP COLUMN `amount`",
            "ALTER TABLE `ss_service_codes` DROP COLUMN `data`",
            "ALTER TABLE `ss_service_codes` ADD COLUMN `price` INT(11)",

            "UPDATE `ss_service_codes` SET `uid` = NULL WHERE `uid` = 0",
            "UPDATE `ss_service_codes` SET `server` = NULL WHERE `server` = 0",
        ]);

        foreach ($this->db->query("SELECT * FROM ss_service_codes") as $row) {
            try {
                $this->db
                    ->statement("UPDATE ss_service_codes SET `price` = ? WHERE `id` = ?")
                    ->execute([array_get($tariffPrice, $row["tariff"]), $row["id"]]);
            } catch (PDOException $e) {
                $this->fileLogger->install("Migrate service_code error. {$e->getMessage()}");
            }
        }

        $this->executeQueries([
            "DELETE FROM `ss_service_codes` WHERE `price` IS NULL",
            "ALTER TABLE `ss_service_codes` MODIFY `price` INT(11) NOT NULL",
            "ALTER TABLE `ss_service_codes` DROP COLUMN `tariff`",
        ]);

        try {
            $this->db->query(
                <<<EOF
ALTER TABLE `ss_service_codes`
  ADD CONSTRAINT `ss_service_codes_server_fk` FOREIGN KEY (`server`) REFERENCES `ss_servers` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE,
  ADD CONSTRAINT `ss_service_codes_price_fk` FOREIGN KEY (`price`) REFERENCES `ss_prices` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE,
  ADD CONSTRAINT `ss_service_codes_user_fk` FOREIGN KEY (`uid`) REFERENCES `ss_users` (`uid`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;
EOF
            );
        } catch (PDOException $e) {
            $this->fileLogger->install("Add service_codes FK error. {$e->getMessage()}");
        }

        $this->db->query("DROP TABLE IF EXISTS `ss_tariffs`");
    }

    private function tariffToSmsPrice($tariff)
    {
        if ($tariff === 26) {
            return 50;
        }

        if (
            in_array($tariff * 100, [
                50,
                100,
                200,
                300,
                400,
                500,
                600,
                700,
                800,
                900,
                1000,
                1100,
                1400,
                1600,
                1900,
                2000,
                2500,
                2600,
            ])
        ) {
            return $tariff * 100;
        }

        return null;
    }
}
