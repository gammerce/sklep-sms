<?php

use App\Install\Migration;

class ReplacePricesPriceWithQuantity extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE `ss_service_codes` ADD COLUMN `quantity` INT(11)");

        $statement = $this->db->query("SELECT sc.id, sp.quantity FROM `ss_service_codes` sc INNER JOIN ss_prices sp on sc.price = sp.id");

        foreach ($statement as $row) {
            $this->db
                ->statement("UPDATE `ss_service_codes` SET `quantity` = ? WHERE `id` = ?")
                ->execute([$row["quantity"], $row["id"]]);
        }

        $this->executeQueries([
            "ALTER TABLE `ss_service_codes` DROP FOREIGN KEY `ss_service_codes_price_fk`",
            "ALTER TABLE `ss_service_codes` DROP COLUMN `price`",
        ]);
    }
}
