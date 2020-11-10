<?php
namespace App\Payment\Admin;

use App\Models\User;
use App\Support\Database;

class AdminPaymentService
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * @param User $admin
     * @param string $platform
     * @return int|string
     */
    public function payByAdmin(User $admin, $platform)
    {
        $this->db
            ->statement("INSERT INTO `ss_payment_admin` (`aid`, `ip`, `platform`) VALUES (?, ?, ?)")
            ->execute([$admin->getId(), $admin->getLastIp(), $platform]);

        return $this->db->lastId();
    }
}
