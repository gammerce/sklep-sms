<?php

use App\Install\Migration;

class LicenseToken extends Migration
{
    public function up()
    {
        $licenseToken = $this->db
            ->query("SELECT `value` FROM `ss_settings` WHERE `key` = 'license_password'")
            ->fetchColumn();
        $this->db
            ->statement("INSERT INTO `ss_settings` SET `key` = 'license_token', `value` = ?")
            ->bindAndExecute([$licenseToken]);
        $this->db->query(
            "DELETE FROM `ss_settings` WHERE `key` IN ('license_login', 'license_password')"
        );
    }
}
