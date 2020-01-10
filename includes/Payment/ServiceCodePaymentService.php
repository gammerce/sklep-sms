<?php
namespace App\Payment;

use App\Loggers\DatabaseLogger;
use App\Models\Purchase;
use App\Repositories\PaymentCodeRespository;
use App\Repositories\ServiceCodeRepository;
use App\ServiceModules\ServiceModule;
use App\System\Database;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class ServiceCodePaymentService
{
    /** @var Translator */
    private $lang;

    /** @var Database */
    private $db;

    /** @var ServiceCodeRepository */
    private $serviceCodeRepository;

    /** @var PaymentCodeRespository */
    private $paymentCodeRepository;

    /** @var DatabaseLogger */
    private $logger;

    public function __construct(
        TranslationManager $translationManager,
        Database $db,
        ServiceCodeRepository $serviceCodeRepository,
        PaymentCodeRespository $paymentCodeRepository,
        DatabaseLogger $logger
    ) {
        $this->lang = $translationManager->user();
        $this->db = $db;
        $this->serviceCodeRepository = $serviceCodeRepository;
        $this->paymentCodeRepository = $paymentCodeRepository;
        $this->logger = $logger;
    }

    /**
     * @param Purchase $purchase
     * @param ServiceModule  $serviceModule
     * @return array|int
     */
    public function payWithServiceCode(Purchase $purchase, ServiceModule $serviceModule)
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

        foreach ($result as $row) {
            if ($serviceModule->serviceCodeValidate($purchase, $row)) {
                $this->serviceCodeRepository->delete($row['id']);

                $paymentCode = $this->paymentCodeRepository->create(
                    $purchase->getPayment('service_code'),
                    $purchase->user->getLastIp(),
                    $purchase->user->getPlatform()
                );

                $this->logger->log(
                    'purchase_code',
                    $purchase->getPayment('service_code'),
                    $purchase->user->getUsername(),
                    $purchase->user->getUid(),
                    $paymentCode->getId()
                );

                return $paymentCode->getId();
            }
        }

        return [
            'status' => "wrong_service_code",
            'text' => $this->lang->t('bad_service_code'),
            'positive' => false,
        ];
    }
}
