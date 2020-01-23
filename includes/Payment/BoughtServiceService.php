<?php
namespace App\Payment;

use App\Loggers\DatabaseLogger;
use App\Models\BoughtService;
use App\Repositories\BoughtServiceRepository;
use App\System\Database;
use App\System\Heart;
use App\System\Mailer;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class BoughtServiceService
{
    /** @var Database */
    private $db;

    /** @var Heart */
    private $heart;

    /** @var Mailer */
    private $mailer;

    /** @var Translator */
    private $lang;

    /** @var BoughtServiceRepository */
    private $boughtServiceRepository;

    /** @var PurchaseInformation */
    private $purchaseInformation;

    /** @var DatabaseLogger */
    private $logger;

    public function __construct(
        Database $db,
        TranslationManager $translationManager,
        Heart $heart,
        Mailer $mailer,
        BoughtServiceRepository $boughtServiceRepository,
        PurchaseInformation $purchaseInformation,
        DatabaseLogger $logger
    ) {
        $this->db = $db;
        $this->heart = $heart;
        $this->mailer = $mailer;
        $this->lang = $translationManager->user();
        $this->boughtServiceRepository = $boughtServiceRepository;
        $this->logger = $logger;
        $this->purchaseInformation = $purchaseInformation;
    }

    /**
     * Add information about purchasing a service
     *
     * @param int $uid
     * @param string $userName
     * @param string $ip
     * @param string $method
     * @param string $paymentId
     * @param string $serviceId
     * @param int $serverId
     * @param string $amount
     * @param string $authData
     * @param string $email
     * @param array $extraData
     *
     * @return int
     */
    public function create(
        $uid,
        $userName,
        $ip,
        $method,
        $paymentId,
        $serviceId,
        $serverId,
        $amount,
        $authData,
        $email,
        $extraData = []
    ) {
        $boughtService = $this->boughtServiceRepository->create(
            $uid,
            $method,
            $paymentId,
            $serviceId,
            $serverId,
            $amount,
            $authData,
            $email,
            $extraData
        );

        $returnMessage = $this->sendEmail($serviceId, $authData, $email, $boughtService);

        $service = $this->heart->getService($serviceId);
        $server = $this->heart->getServer($serverId);
        $amount = $amount != -1 ? "{$amount} {$service->getTag()}" : $this->lang->t('forever');

        $this->logger->log(
            'bought_service_info',
            $serviceId,
            $authData,
            $amount,
            $server ? $server->getName() : '',
            $paymentId,
            $returnMessage,
            $userName,
            $uid,
            $ip
        );

        return $boughtService->getId();
    }

    private function sendEmail($service, $authData, $email, BoughtService $boughtService)
    {
        if (!strlen($email)) {
            return $this->lang->t('none');
        }

        $message = $this->purchaseInformation->get([
            'purchase_id' => $boughtService->getId(),
            'action' => "email",
        ]);

        if (!strlen($message)) {
            return $this->lang->t('none');
        }

        $title =
            $service == 'charge_wallet'
                ? $this->lang->t('charge_wallet')
                : $this->lang->t('purchase');

        $ret = $this->mailer->send($email, $authData, $title, $message);

        if ($ret == "not_sent") {
            return "nie wysłano";
        }

        if ($ret == "sent") {
            return "wysłano";
        }

        return $ret;
    }
}
