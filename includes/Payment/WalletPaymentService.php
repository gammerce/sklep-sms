<?php
namespace App\Payment;

use App\Models\User;
use App\System\Database;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class WalletPaymentService
{
    /** @var Translator */
    private $lang;

    /** @var Database */
    private $db;

    public function __construct(TranslationManager $translationManager, Database $db)
    {
        $this->lang = $translationManager->user();
        $this->db = $db;
    }

    /**
     * @param int  $cost
     * @param User $user
     * @return array|int|string
     */
    public function payWithWallet($cost, $user)
    {
        if ($cost > $user->getWallet()) {
            return [
                'status' => "no_money",
                'text' => $this->lang->t('not_enough_money'),
                'positive' => false,
            ];
        }

        $this->chargeWallet($user->getUid(), -$cost);

        $this->db
            ->statement(
                "INSERT INTO `ss_payment_wallet` " . "SET `cost` = ?, `ip` = ?, `platform` = ?"
            )
            ->execute([$cost, $user->getLastIp(), $user->getPlatform()]);

        return $this->db->lastId();
    }

    /**
     * @param int $uid
     * @param int $amount
     */
    private function chargeWallet($uid, $amount)
    {
        $this->db
            ->statement("UPDATE `ss_users` " . "SET `wallet` = `wallet` + ? " . "WHERE `uid` = ?")
            ->execute([$amount, $uid]);
    }
}
