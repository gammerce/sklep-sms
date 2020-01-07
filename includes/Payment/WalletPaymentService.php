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
        // Sprawdzanie, czy jest wystarczająca ilość kasy w portfelu
        if ($cost > $user->getWallet()) {
            return [
                'status' => "no_money",
                'text' => $this->lang->t('not_enough_money'),
                'positive' => false,
            ];
        }

        // Zabieramy kasę z portfela
        $this->chargeWallet($user->getUid(), -$cost);

        // Dodajemy informacje o płatności portfelem
        $this->db->query(
            $this->db->prepare(
                "INSERT INTO `" .
                    TABLE_PREFIX .
                    "payment_wallet` " .
                    "SET `cost` = '%d', `ip` = '%s', `platform` = '%s'",
                [$cost, $user->getLastIp(), $user->getPlatform()]
            )
        );

        return $this->db->lastId();
    }

    /**
     * @param int $uid
     * @param int $amount
     */
    private function chargeWallet($uid, $amount)
    {
        $this->db->query(
            $this->db->prepare(
                "UPDATE `" .
                    TABLE_PREFIX .
                    "users` " .
                    "SET `wallet` = `wallet` + '%d' " .
                    "WHERE `uid` = '%d'",
                [$amount, $uid]
            )
        );
    }
}
