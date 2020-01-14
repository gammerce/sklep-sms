<?php
namespace App\Payment;

use App\Loggers\DatabaseLogger;
use App\Models\Purchase;
use App\Repositories\PaymentCodeRepository;
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

    /** @var PaymentCodeRepository */
    private $paymentCodeRepository;

    /** @var DatabaseLogger */
    private $logger;

    public function __construct(
        TranslationManager $translationManager,
        Database $db,
        ServiceCodeRepository $serviceCodeRepository,
        PaymentCodeRepository $paymentCodeRepository,
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
        $statement = $this->db->statement(
            "SELECT * FROM `" .
                TABLE_PREFIX .
                "service_codes` " .
                "WHERE `code` = ? " .
                "AND `service` = ? " .
                "AND `price` = ? " .
                "AND (`server` IS NULL OR `server` = ?) " .
                "AND (`uid` IS NULL OR `uid` = ?)"
        );
        $statement->execute(
            [
                $purchase->getPayment('service_code'),
                $purchase->getService(),
                $purchase->getPrice()->getId(),
                $purchase->getOrder('server'),
                $purchase->user->getUid(),
            ]
        );

        foreach ($statement as $row) {
            $serviceCode = $this->serviceCodeRepository->mapToModel($row);

            if ($serviceModule->serviceCodeValidate($purchase, $serviceCode)) {
                $this->serviceCodeRepository->delete($serviceCode->getId());

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
