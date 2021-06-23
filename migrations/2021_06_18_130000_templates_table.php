<?php

use App\Install\Migration;

class TemplatesTable extends Migration
{
    public function up()
    {
        $this->db->query(
            <<<EOF
CREATE TABLE IF NOT EXISTS `ss_templates` (
  `id`              INT(11)      NOT NULL AUTO_INCREMENT,
  `theme`           VARCHAR(32)  NOT NULL,
  `name`            VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `lang`            VARCHAR(16)  NULL,
  `content`         TEXT         NOT NULL,
  `created_at`      TIMESTAMP    NOT NULL,
  `updated_at`      TIMESTAMP    NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `theme_name_lang` (`theme`, `name`, `lang`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4 COLLATE utf8mb4_bin
EOF
        );
    }
}
