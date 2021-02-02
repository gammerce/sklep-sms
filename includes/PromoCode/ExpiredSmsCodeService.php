<?php
namespace App\PromoCode;

use App\Support\Database;

class ExpiredSmsCodeService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function deleteExpired(): void
    {
        $this->db->query(
            "DELETE FROM `ss_sms_codes` WHERE `expires_at` IS NOT NULL AND `expires_at` <= NOW()"
        );
    }
}
