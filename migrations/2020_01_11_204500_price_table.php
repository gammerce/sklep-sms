<?php

use App\Install\Migration;

class PriceTable extends Migration
{
    public function up()
    {
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
EOF,
            <<<EOF
ALTER TABLE `ss_prices`
  ADD CONSTRAINT `ss_prices_service_fk` FOREIGN KEY (`service`) REFERENCES `ss_services` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE,
  ADD CONSTRAINT `ss_prices_server_fk` FOREIGN KEY (`server`) REFERENCES `ss_servers` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;
EOF
        ,
        ]);

        foreach ($this->db->query("SELECT * FROM ss_pricelist") as $row) {
            // TODO Finish it
            $this->db->statement(
                <<<EOF
INSERT INTO ss_prices (`service`, `server`, `sms_price`, `transfer_price`, `quantity`)
VALUES ()
EOF
            );
        }
    }
}
