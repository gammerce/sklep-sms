<?php
namespace App\Payment\Wallet;

use App\Models\User;
use App\Support\Database;

class WalletPaymentService
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * @param int  $cost
     * @param User $user
     * @return int
     */
    public function payWithWallet($cost, $user)
    {
        if ($cost > $user->getWallet()) {
            throw new NotEnoughFundsException();
        }

        $this->chargeWallet($user->getUid(), -$cost);

        $this->db
            ->statement("INSERT INTO `ss_payment_wallet` SET `cost` = ?, `ip` = ?, `platform` = ?")
            ->execute([$cost, $user->getLastIp(), $user->getPlatform()]);

        return $this->db->lastId();
    }

    /**
     * @param int $uid
     * @param int $quantity
     */
    public function chargeWallet($uid, $quantity)
    {
        $this->db
            ->statement("UPDATE `ss_users` SET `wallet` = `wallet` + ? WHERE `uid` = ?")
            ->execute([$quantity, $uid]);
    }
}
