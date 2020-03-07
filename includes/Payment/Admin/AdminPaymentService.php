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
     * @return int|string
     */
    public function payByAdmin(User $admin)
    {
        $this->db
            ->statement("INSERT INTO `ss_payment_admin` (`aid`, `ip`, `platform`) VALUES (?, ?, ?)")
            ->execute([$admin->getUid(), $admin->getLastIp(), $admin->getPlatform()]);

        return $this->db->lastId();
    }
}
