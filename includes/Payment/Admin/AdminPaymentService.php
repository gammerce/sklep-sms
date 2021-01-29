<?php
namespace App\Payment\Admin;

use App\Models\User;
use App\Support\Database;

class AdminPaymentService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * @param User $admin
     * @param string $ip
     * @param string $platform
     * @return int
     */
    public function payByAdmin(User $admin, $ip, $platform)
    {
        $this->db
            ->statement("INSERT INTO `ss_payment_admin` (`aid`, `ip`, `platform`) VALUES (?, ?, ?)")
            ->execute([$admin->getId(), $ip, $platform]);

        return $this->db->lastId();
    }
}
