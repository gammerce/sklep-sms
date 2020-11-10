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
     * @param int $cost
     * @param User $user
     * @param string $ip
     * @param string $platform
     * @return int
     * @throws NotEnoughFundsException
     */
    public function payWithWallet($cost, User $user, $ip, $platform)
    {
        if ($cost > $user->getWallet()->asInt()) {
            throw new NotEnoughFundsException();
        }

        $this->chargeWallet($user, -$cost);

        $this->db
            ->statement("INSERT INTO `ss_payment_wallet` SET `cost` = ?, `ip` = ?, `platform` = ?")
            ->execute([$cost, $ip, $platform]);

        return $this->db->lastId();
    }

    /**
     * @param User $user
     * @param int $quantity
     */
    public function chargeWallet(User $user, $quantity)
    {
        $this->db
            ->statement("UPDATE `ss_users` SET `wallet` = `wallet` + ? WHERE `uid` = ?")
            ->execute([$quantity, $user->getId()]);

        $user->setWallet($user->getWallet()->asInt() + $quantity);
    }
}
