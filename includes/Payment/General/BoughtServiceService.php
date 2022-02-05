<?php
namespace App\Payment\General;

use App\Loggers\DatabaseLogger;
use App\Managers\ServerManager;
use App\Managers\ServiceManager;
use App\Managers\UserManager;
use App\Models\BoughtService;
use App\Repositories\BoughtServiceRepository;
use App\Support\Mailer;
use App\Theme\Template;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class BoughtServiceService
{
    private BoughtServiceRepository $boughtServiceRepository;
    private DatabaseLogger $logger;
    private Mailer $mailer;
    private PurchaseInformation $purchaseInformation;
    private ServerManager $serverManager;
    private ServiceManager $serviceManager;
    private Template $template;
    private Translator $lang;
    private UserManager $userManager;

    public function __construct(
        BoughtServiceRepository $boughtServiceRepository,
        DatabaseLogger $logger,
        Mailer $mailer,
        PurchaseInformation $purchaseInformation,
        ServerManager $serverManager,
        ServiceManager $serviceManager,
        Template $template,
        TranslationManager $translationManager,
        UserManager $userManager
    ) {
        $this->boughtServiceRepository = $boughtServiceRepository;
        $this->lang = $translationManager->user();
        $this->logger = $logger;
        $this->mailer = $mailer;
        $this->purchaseInformation = $purchaseInformation;
        $this->serverManager = $serverManager;
        $this->serviceManager = $serviceManager;
        $this->template = $template;
        $this->userManager = $userManager;
    }

    /**
     * Add information about purchasing a service
     *
     * @param int $userId
     * @param string $userName
     * @param string $ip
     * @param string $method
     * @param string $paymentId
     * @param string|null $invoiceId
     * @param string $serviceId
     * @param int $serverId
     * @param int|null $quantity
     * @param string $authData
     * @param string $email
     * @param string $promoCode
     * @param array $extraData
     * @return int
     */
    public function create(
        $userId,
        $userName,
        $ip,
        $method,
        $paymentId,
        $invoiceId,
        $serviceId,
        $serverId,
        $quantity,
        $authData,
        $email,
        $promoCode,
        $extraData = []
    ): int {
        $forever = $quantity === null;

        $boughtService = $this->boughtServiceRepository->create(
            $userId,
            $method,
            $paymentId,
            $invoiceId,
            $serviceId,
            $serverId,
            $forever ? -1 : $quantity,
            $authData,
            $email,
            $promoCode,
            $extraData
        );

        $returnMessage = $this->sendEmail($serviceId, $email, $boughtService);

        $service = $this->serviceManager->get($serviceId);
        $server = $this->serverManager->get($serverId);
        $quantity = $forever ? $this->lang->t("forever") : "{$quantity} {$service->getTag()}";

        $this->logger->log(
            "log_bought_service_info",
            $serviceId,
            $authData,
            $quantity,
            $server ? $server->getName() : "",
            $paymentId,
            $promoCode,
            $email,
            $promoCode,
            $returnMessage,
            $userName,
            $userId,
            $ip
        );

        return $boughtService->getId();
    }

    private function sendEmail(string $service, string $email, BoughtService $boughtService): string
    {
        if (!strlen($email)) {
            return $this->lang->t("none");
        }

        $content = $this->purchaseInformation->get([
            "purchase_id" => $boughtService->getId(),
            "action" => "email",
        ]);

        if (!strlen($content)) {
            return $this->lang->t("none");
        }

        $user = $this->userManager->get($boughtService->getUserId());
        $who = $user->getForename() ?: $boughtService->getAuthData();

        $title =
            $service == "charge_wallet"
                ? $this->lang->t("charge_wallet")
                : $this->lang->t("purchase");

        $text = $this->template->renderNoComments("emails/layout", [
            "who" => $who,
            "content" => $content,
        ]);

        return $this->mailer->send($email, $who, $title, $text);
    }
}
