<?php
namespace App\Payment\General;

use App\Loggers\DatabaseLogger;
use App\Managers\ServerManager;
use App\Managers\ServiceManager;
use App\Models\BoughtService;
use App\Repositories\BoughtServiceRepository;
use App\Support\Mailer;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class BoughtServiceService
{
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

    /** @var ServerManager */
    private $serverManager;

    /** @var ServiceManager */
    private $serviceManager;

    public function __construct(
        TranslationManager $translationManager,
        Mailer $mailer,
        ServerManager $serverManager,
        ServiceManager $serviceManager,
        BoughtServiceRepository $boughtServiceRepository,
        PurchaseInformation $purchaseInformation,
        DatabaseLogger $logger
    ) {
        $this->mailer = $mailer;
        $this->lang = $translationManager->user();
        $this->boughtServiceRepository = $boughtServiceRepository;
        $this->logger = $logger;
        $this->purchaseInformation = $purchaseInformation;
        $this->serverManager = $serverManager;
        $this->serviceManager = $serviceManager;
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
     * @param int|null $quantity
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
        $quantity,
        $authData,
        $email,
        $extraData = []
    ) {
        $forever = $quantity === null;

        $boughtService = $this->boughtServiceRepository->create(
            $uid,
            $method,
            $paymentId,
            $serviceId,
            $serverId,
            $forever ? -1 : $quantity,
            $authData,
            $email,
            $extraData
        );

        $returnMessage = $this->sendEmail($serviceId, $authData, $email, $boughtService);

        $service = $this->serviceManager->getService($serviceId);
        $server = $this->serverManager->getServer($serverId);
        $quantity = $forever ? $this->lang->t('forever') : "{$quantity} {$service->getTag()}";

        $this->logger->log(
            'log_bought_service_info',
            $serviceId,
            $authData,
            $quantity,
            $server ? $server->getName() : '',
            $paymentId,
            $email,
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
