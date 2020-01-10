<?php
namespace App\Payment;

use App\Models\User;
use App\System\Database;

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
        // Dodawanie informacji o płatności
        $this->db->query(
            $this->db->prepare(
                "INSERT INTO `" .
                    TABLE_PREFIX .
                    "payment_admin` (`aid`, `ip`, `platform`) " .
                    "VALUES ('%d', '%s', '%s')",
                [$admin->getUid(), $admin->getLastIp(), $admin->getPlatform()]
            )
        );

        return $this->db->lastId();
    }
}
