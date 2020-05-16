<?php

use App\Install\Migration;

class ChangeForeignColumnNames extends Migration
{
    public function up()
    {
        $this->db->query("DROP TABLE IF EXISTS `ss_pricelist`");

        $this->executeQueries([
            "ALTER TABLE `ss_prices` DROP FOREIGN KEY `ss_prices_server_fk`",
            "ALTER TABLE `ss_prices` CHANGE COLUMN `server` `server_id` INT(11)",
            "ALTER TABLE `ss_user_service` CHANGE COLUMN `uid` `user_id` INT(11)",
            "ALTER TABLE `ss_user_service_extra_flags` DROP FOREIGN KEY `ss_user_service_extra_flags_ibfk_3`",
            "ALTER TABLE `ss_user_service_extra_flags` CHANGE COLUMN `server` `server_id` INT(11)",
            "ALTER TABLE `ss_bought_services` CHANGE COLUMN `server` `server_id` INT(11)",
            "ALTER TABLE `ss_bought_services` CHANGE COLUMN `uid` `user_id` INT(11)",
            "ALTER TABLE `ss_players_flags` CHANGE COLUMN `server` `server_id` INT(11) NOT NULL",
        ]);

        $this->db->query(
            <<<EOF
ALTER TABLE `ss_prices`
  ADD CONSTRAINT `ss_prices_server_fk` FOREIGN KEY (`server_id`) REFERENCES `ss_servers` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;
EOF
        );

        $this->db->query(
            <<<EOF
ALTER TABLE `ss_user_service_extra_flags`
  ADD CONSTRAINT `ss_user_service_extra_flags_ibfk_3` FOREIGN KEY (`server_id`) REFERENCES `ss_servers` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;
EOF
        );
    }
}
