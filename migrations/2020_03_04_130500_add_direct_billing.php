<?php

use App\Install\Migration;

class AddDirectBilling extends Migration
{
    public function up()
    {
        $this->executeQueries([
            "INSERT INTO `ss_settings` SET `key` = 'direct_billing_platform', `value` = ''",
            "ALTER TABLE `ss_prices` ADD `direct_billing_price`  INT(11) DEFAULT NULL",
            "DROP TABLE IF EXISTS `ss_payment_direct_billing`",
            <<<EOF
CREATE TABLE IF NOT EXISTS `ss_payment_direct_billing` (
  `id`              INT(11)          NOT NULL AUTO_INCREMENT,
  `external_id`     VARCHAR(64)      NOT NULL,
  `income`          INT(11)          NOT NULL,
  `cost`            INT(11)          NOT NULL,
  `free`            TINYINT(1)       NOT NULL,
  `ip`              VARCHAR(64)      DEFAULT NULL,
  `platform`        TEXT             DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;
EOF
        ,
        ]);
    }
}
