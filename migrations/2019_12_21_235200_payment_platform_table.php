<?php

use App\Install\Migration;

class PaymentPlatformTable extends Migration
{
    public function up()
    {
        $queries = [
            "DROP TABLE IF EXISTS `ss_payment_platforms`",
            <<<EOF
CREATE TABLE IF NOT EXISTS `ss_payment_platforms` (
  `id`          INT(11)          NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(32)      NOT NULL DEFAULT '',
  `platform`    VARCHAR(64)      NOT NULL,
  `data`        VARCHAR(512)     NOT NULL DEFAULT ''
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;
EOF
        ,
        ];
        $this->executeQueries($queries);
    }
}
