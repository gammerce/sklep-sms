<?php
namespace App\Services;

use App\Support\Database;

class ExpiredSmsCodeService
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function deleteExpired()
    {
        $this->db->query(
            "DELETE FROM `ss_sms_codes` WHERE `expires_at` IS NOT NULL AND `expires_at` <= NOW()"
        );
    }
}
