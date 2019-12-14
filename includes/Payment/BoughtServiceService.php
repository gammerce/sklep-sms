<?php
namespace App\Payment;

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
     * @param string  $userName
     * @param string  $ip
     * @param string  $method
     * @param string  $paymentId
     * @param string  $service
     * @param integer $server
     * @param string  $amount
     * @param string  $authData
     * @param string  $email
     * @param array   $extraData
     *
     * @return int
     */
    public function create(
        $uid,
        $userName,
        $ip,
        $method,
        $paymentId,
        $service,
        $server,
        $amount,
        $authData,
        $email,
        $extraData = []
    ) {
        $boughtService = $this->boughtServiceRepository->create(
            $uid,
            $method,
            $paymentId,
            $service,
            $server,
            $amount,
            $authData,
            $email,
            $extraData
        );

        $ret = $this->lang->translate('none');
        if (strlen($email)) {
            $message = purchase_info([
                'purchase_id' => $boughtService->getId(),
                'action' => "email",
            ]);
            if (strlen($message)) {
                $title =
                    $service == 'charge_wallet'
                        ? $this->lang->translate('charge_wallet')
                        : $this->lang->translate('purchase');
                $ret = $this->mailer->send($email, $authData, $title, $message);
            }

            if ($ret == "not_sent") {
                $ret = "nie wysłano";
            } elseif ($ret == "sent") {
                $ret = "wysłano";
            }
        }

        $tempService = $this->heart->getService($service);
        $tempServer = $this->heart->getServer($server);
        $amount =
            $amount != -1 ? "{$amount} {$tempService['tag']}" : $this->lang->translate('forever');
        log_to_db(
            $this->langShop->sprintf(
                $this->langShop->translate('bought_service_info'),
                $service,
                $authData,
                $amount,
                $tempServer['name'],
                $paymentId,
                $ret,
                $userName,
                $uid,
                $ip
            )
        );
        unset($tempServer);

        return $boughtService->getId();
    }
}
