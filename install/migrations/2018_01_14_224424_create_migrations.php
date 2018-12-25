<?php

use Install\Migration;

class CreateMigrations extends Migration
{
    public function up()
    {
        $this->db->query("DROP TABLE IF EXISTS `ss_migrations`;");
        $this->db->query(
            <<<EOF
CREATE TABLE IF NOT EXISTS `ss_migrations` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;
EOF
        );
    }
}
