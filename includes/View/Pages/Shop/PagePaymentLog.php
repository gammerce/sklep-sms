<?php
namespace App\View\Pages\Shop;

use App\Managers\ServerManager;
use App\Managers\ServiceManager;
use App\Managers\ServiceModuleManager;
use App\Repositories\TransactionRepository;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\Services\PriceTextService;
use App\Support\Database;
use App\Support\Template;
use App\System\Auth;
use App\Translation\TranslationManager;
use App\View\Interfaces\IBeLoggedMust;
use App\View\Pages\Page;
use App\View\PaginationService;
use Symfony\Component\HttpFoundation\Request;

class PagePaymentLog extends Page implements IBeLoggedMust
{
    const PAGE_ID = "payment_log";

    /** @var PriceTextService */
    private $priceTextService;

    /** @var TransactionRepository */
    private $transactionRepository;

    /** @var Auth */
    private $auth;

    /** @var Database */
    private $db;

    /** @var PaginationService */
    private $paginationService;

    /** @var ServiceModuleManager */
    private $serviceModuleManager;

    /** @var ServiceManager */
    private $serviceManager;

    /** @var ServerManager */
    private $serverManager;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        PriceTextService $priceTextService,
        TransactionRepository $transactionRepository,
        Auth $auth,
        Database $db,
        PaginationService $paginationService,
        ServiceModuleManager $serviceModuleManager,
        ServiceManager $serviceManager,
        ServerManager $serverManager
    ) {
        parent::__construct($template, $translationManager);

        $this->priceTextService = $priceTextService;
        $this->transactionRepository = $transactionRepository;
        $this->auth = $auth;
        $this->db = $db;
        $this->paginationService = $paginationService;
        $this->serviceModuleManager = $serviceModuleManager;
        $this->serviceManager = $serviceManager;
        $this->serverManager = $serverManager;
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("payment_log");
    }

    public function getContent(Request $request)
    {
        $user = $this->auth->user();

        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS * FROM ({$this->transactionRepository->getQuery()}) as t " .
                "WHERE t.user_id = ? " .
                "ORDER BY t.timestamp DESC " .
                "LIMIT ?, ?"
        );
        $statement->execute(array_merge([$user->getId()], get_row_limit($request, 10)));
        $rowsCount = $this->db->query("SELECT FOUND_ROWS()")->fetchColumn();

        $paymentLogs = "";
        foreach ($statement as $row) {
            $transaction = $this->transactionRepository->mapToModel($row);
            $date = $transaction->getTimestamp();
            $cost = $this->priceTextService->getPriceText($transaction->getCost());

            $serviceModule = $this->serviceModuleManager->get($transaction->getServiceId());
            if ($serviceModule instanceof IServicePurchaseWeb) {
                $logInfo = $serviceModule->purchaseInfo("payment_log", $transaction);
                $desc = $logInfo["text"];
                $class = $logInfo["class"];
            } else {
                $service = $this->serviceManager->get($transaction->getServiceId());
                $server = $this->serverManager->getServer($transaction->getServerId());
                $desc = $this->lang->t(
                    "service_was_bought",
                    $service ? $service->getNameI18n() : "",
                    $server ? $server->getName() : ""
                );
                $class = "outcome";
            }

            $paymentLogs .= $this->template->render(
                "shop/components/payment_log/payment_log_brick",
                compact("class", "date", "cost", "desc")
            );
        }

        $paginationContent = $this->paginationService->createPagination(
            $rowsCount,
            get_current_page($request),
            $request->getPathInfo(),
            $request->query->all(),
            10
        );
        $paginationClass = $paginationContent ? "" : "is-hidden";

        return $this->template->render(
            "shop/pages/payment_log",
            compact("paymentLogs", "paginationClass", "paginationContent")
        );
    }
}
