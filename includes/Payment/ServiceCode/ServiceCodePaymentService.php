<?php
namespace App\Payment\ServiceCode;

use App\Loggers\DatabaseLogger;
use App\Models\Purchase;
use App\Repositories\PaymentCodeRepository;
use App\Repositories\ServiceCodeRepository;
use App\Support\Database;
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
     * @return int|null
     */
    public function payWithServiceCode(Purchase $purchase)
    {
        // TODO Remove price usage
        $statement = $this->db->statement(
            <<<EOF
            SELECT * FROM `ss_service_codes` 
            WHERE `code` = ?
            AND `service` = ?
            AND `price` = ?
            AND (`server` IS NULL OR `server` = ?)
            AND (`uid` IS NULL OR `uid` = ?)
            LIMIT 1
EOF
        );
        $statement->execute([
            $purchase->getPayment(Purchase::PAYMENT_SERVICE_CODE),
            $purchase->getServiceId(),
            $purchase->getPrice()->getId(),
            $purchase->getOrder(Purchase::ORDER_SERVER),
            $purchase->user->getUid(),
        ]);

        $row = $statement->fetch();

        if (!$row) {
            return null;
        }

        $serviceCode = $this->serviceCodeRepository->mapToModel($row);

        $this->serviceCodeRepository->delete($serviceCode->getId());

        $paymentCode = $this->paymentCodeRepository->create(
            $purchase->getPayment(Purchase::PAYMENT_SERVICE_CODE),
            $purchase->user->getLastIp(),
            $purchase->user->getPlatform()
        );

        $this->logger->logWithUser(
            $purchase->user,
            'log_purchase_code',
            $purchase->getPayment(Purchase::PAYMENT_SERVICE_CODE),
            $paymentCode->getId()
        );

        return $paymentCode->getId();
    }
}
