<?php
namespace App\Payment;

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

    /** @var Translator */
    private $langShop;

    /** @var BoughtServiceRepository */
    private $boughtServiceRepository;

    public function __construct(
        Database $db,
        TranslationManager $translationManager,
        Heart $heart,
        Mailer $mailer,
        BoughtServiceRepository $boughtServiceRepository
    ) {
        $this->db = $db;
        $this->heart = $heart;
        $this->mailer = $mailer;
        $this->lang = $translationManager->user();
        $this->langShop = $translationManager->shop();
        $this->boughtServiceRepository = $boughtServiceRepository;
    }

    /**
     * Add information about purchasing a service
     *
     * @param integer $uid
     * @param string $userName
     * @param string $ip
     * @param string $method
     * @param string $paymentId
     * @param string $serviceId
     * @param integer $serverId
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

        log_to_db(
            $this->langShop->t(
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
            )
        );

        return $boughtService->getId();
    }

    private function sendEmail($service, $authData, $email, BoughtService $boughtService)
    {
        if (!strlen($email)) {
            return $this->lang->t('none');
        }

        $message = purchase_info([
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
