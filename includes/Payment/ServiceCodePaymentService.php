<?php
namespace App\Payment;

use App\Models\Purchase;
use App\Repositories\PaymentCodeRespository;
use App\Repositories\ServiceCodeRepository;
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

    /** @var ServiceCodeRepository */
    private $serviceCodeRepository;

    /** @var PaymentCodeRespository */
    private $paymentCodeRespository;

    public function __construct(
        TranslationManager $translationManager,
        Database $db,
        ServiceCodeRepository $serviceCodeRepository,
        PaymentCodeRespository $paymentCodeRespository
    ) {
        $this->lang = $translationManager->user();
        $this->langShop = $translationManager->shop();
        $this->db = $db;
        $this->serviceCodeRepository = $serviceCodeRepository;
        $this->paymentCodeRespository = $paymentCodeRespository;
    }

    /**
     * @param Purchase $purchase
     * @param Service  $serviceModule
     * @return array|int
     */
    public function payWithServiceCode(Purchase $purchase, Service $serviceModule)
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
                $this->serviceCodeRepository->delete($row['id']);

                $paymentCode = $this->paymentCodeRespository->create(
                    $purchase->getPayment('service_code'),
                    $purchase->user->getLastIp(),
                    $purchase->user->getPlatform()
                );

                log_to_db(
                    $this->langShop->sprintf(
                        $this->langShop->translate('purchase_code'),
                        $purchase->getPayment('service_code'),
                        $purchase->user->getUsername(),
                        $purchase->user->getUid(),
                        $paymentCode->getId()
                    )
                );

                return $paymentCode->getId();
            }
        }

        return [
            'status' => "wrong_service_code",
            'text' => $this->lang->translate('bad_service_code'),
            'positive' => false,
        ];
    }
}
