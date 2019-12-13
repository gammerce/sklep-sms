<?php
namespace App\Payment;

use App\Models\Purchase;
use App\Services\Service;
use App\System\Database;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class ServiceCodePaymentService
{
    /** @var Translator */
    private $lang;

    /** @var Translator */
    private $langShop;

    /** @var Database */
    private $db;

    public function __construct(TranslationManager $translationManager, Database $db)
    {
        $this->lang = $translationManager->user();
        $this->langShop = $translationManager->shop();
        $this->db = $db;
    }

    /**
     * @param Purchase $purchase
     * @param Service  $serviceModule
     *
     * @return array|int|string
     */
    public function payServiceCode(Purchase $purchase, $serviceModule)
    {
        $result = $this->db->query(
            $this->db->prepare(
                "SELECT * FROM `" .
                    TABLE_PREFIX .
                    "service_codes` " .
                    "WHERE `code` = '%s' " .
                    "AND `service` = '%s' " .
                    "AND (`server` = '0' OR `server` = '%s') " .
                    "AND (`tariff` = '0' OR `tariff` = '%d') " .
                    "AND (`uid` = '0' OR `uid` = '%s')",
                [
                    $purchase->getPayment('service_code'),
                    $purchase->getService(),
                    $purchase->getOrder('server'),
                    $purchase->getTariff(),
                    $purchase->user->getUid(),
                ]
            )
        );

        while ($row = $this->db->fetchArrayAssoc($result)) {
            if ($serviceModule->serviceCodeValidate($purchase, $row)) {
                // Znalezlismy odpowiedni kod
                $this->db->query(
                    $this->db->prepare(
                        "DELETE FROM `" . TABLE_PREFIX . "service_codes` " . "WHERE `id` = '%d'",
                        [$row['id']]
                    )
                );

                // Dodajemy informacje o płatności kodem
                $this->db->query(
                    $this->db->prepare(
                        "INSERT INTO `" .
                            TABLE_PREFIX .
                            "payment_code` " .
                            "SET `code` = '%s', `ip` = '%s', `platform` = '%s'",
                        [
                            $purchase->getPayment('service_code'),
                            $purchase->user->getLastIp(),
                            $purchase->user->getPlatform(),
                        ]
                    )
                );
                $paymentId = $this->db->lastId();

                log_to_db(
                    $this->langShop->sprintf(
                        $this->langShop->translate('purchase_code'),
                        $purchase->getPayment('service_code'),
                        $purchase->user->getUsername(),
                        $purchase->user->getUid(),
                        $paymentId
                    )
                );

                return $paymentId;
            }
        }

        return [
            'status' => "wrong_service_code",
            'text' => $this->lang->translate('bad_service_code'),
            'positive' => false,
        ];
    }
}
