<?php
namespace App\Payment\Wallet;

use App\Models\User;
use App\Support\Database;
use App\Support\Money;

class WalletPaymentService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * @param Money $cost
     * @param User $user
     * @param string $ip
     * @param string $platform
     * @return int
     * @throws NotEnoughFundsException
     */
    public function payWithWallet(Money $cost, User $user, $ip, $platform): int
    {
        if ($cost->asInt() > $user->getWallet()->asInt()) {
            throw new NotEnoughFundsException();
        }

        $this->chargeWallet($user, -$cost->asInt());

        $this->db
            ->statement("INSERT INTO `ss_payment_wallet` SET `cost` = ?, `ip` = ?, `platform` = ?")
            ->bindAndExecute([$cost->asInt(), $ip, $platform]);

        return $this->db->lastId();
    }

    /**
     * @param User $user
     * @param int $quantity
     */
    public function chargeWallet(User $user, $quantity): void
    {
        $this->db
            ->statement("UPDATE `ss_users` SET `wallet` = `wallet` + ? WHERE `uid` = ?")
            ->bindAndExecute([$quantity, $user->getId()]);

        $user->setWallet($user->getWallet()->add($quantity));
    }
}
