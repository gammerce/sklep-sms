<?php

use App\Install\Migration;

class ServiceIdUTF8MB4 extends Migration
{
    public function up()
    {
        $this->db->query("DROP TABLE IF EXISTS `ss_pricelist`");

        $this->executeQueries([
            "ALTER TABLE `ss_prices` DROP FOREIGN KEY `ss_prices_service_fk`",
            "ALTER TABLE `ss_servers_services` DROP FOREIGN KEY `ss_servers_services_ibfk_2`",
            "ALTER TABLE `ss_user_service` DROP FOREIGN KEY `ss_user_service_ibfk_1`",
            "ALTER TABLE `ss_user_service_extra_flags` DROP FOREIGN KEY `ss_user_service_extra_flags_ibfk_2`",
            "ALTER TABLE `ss_user_service_mybb_extra_groups` DROP FOREIGN KEY `ss_user_service_mybb_extra_groups_ibfk_2`",
        ]);

        $this->executeQueries([
            "ALTER TABLE `ss_promo_codes` CHANGE COLUMN `service` `service_id` VARCHAR(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin",
            "ALTER TABLE `ss_prices` CHANGE COLUMN `service` `service_id` VARCHAR(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin",
            "ALTER TABLE `ss_servers_services` CHANGE COLUMN `service_id` `service_id` VARCHAR(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin",
            "ALTER TABLE `ss_user_service` CHANGE COLUMN `service` `service_id` VARCHAR(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin",
            "ALTER TABLE `ss_user_service_extra_flags` CHANGE COLUMN `service` `service_id` VARCHAR(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin",
            "ALTER TABLE `ss_user_service_mybb_extra_groups` CHANGE COLUMN `service` `service_id` VARCHAR(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin",
            "ALTER TABLE `ss_services` CHANGE COLUMN `id` `id` VARCHAR(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL",
        ]);

        $this->db->query(
            <<<EOF
ALTER TABLE `ss_prices`
  ADD CONSTRAINT `ss_prices_service_fk` FOREIGN KEY (`service_id`) REFERENCES `ss_services` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;
EOF
        );

        $this->db->query(
            <<<EOF
ALTER TABLE `ss_user_service`
  ADD CONSTRAINT `ss_user_service_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `ss_services` (`id`)
  ON DELETE RESTRICT
  ON UPDATE CASCADE;
EOF
        );

        $this->db->query(
            <<<EOF
ALTER TABLE `ss_servers_services`
  ADD CONSTRAINT `ss_servers_services_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `ss_services` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;
EOF
        );

        $this->db->query(
            <<<EOF
ALTER TABLE `ss_promo_codes`
  ADD CONSTRAINT `ss_promo_codes_service_fk` FOREIGN KEY (`service_id`) REFERENCES `ss_services` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;
EOF
        );

        $this->db->query(
            <<<EOF
ALTER TABLE `ss_user_service_extra_flags`
  ADD CONSTRAINT `ss_user_service_extra_flags_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `ss_services` (`id`)
  ON DELETE RESTRICT 
  ON UPDATE CASCADE;
EOF
        );

        $this->db->query(
            <<<EOF
ALTER TABLE `ss_user_service_mybb_extra_groups`
  ADD CONSTRAINT `ss_user_service_mybb_extra_groups_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `ss_services` (`id`)
  ON DELETE RESTRICT 
  ON UPDATE CASCADE;
EOF
        );
    }
}
