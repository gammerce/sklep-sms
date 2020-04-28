<?php
namespace App\View\Pages\Shop;

use App\Repositories\TransactionRepository;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\Services\PriceTextService;
use App\Support\Database;
use App\Support\Template;
use App\System\Auth;
use App\System\Heart;
use App\Translation\TranslationManager;
use App\View\CurrentPage;
use App\View\Interfaces\IBeLoggedMust;
use App\View\Pages\Page;
use App\View\PaginationService;
use App\Managers\ServiceModuleManager;
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

    /** @var CurrentPage */
    private $currentPage;

    /** @var Heart */
    private $heart;

    /** @var ServiceModuleManager */
    private $serviceModuleManager;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        PriceTextService $priceTextService,
        TransactionRepository $transactionRepository,
        Auth $auth,
        Database $db,
        PaginationService $paginationService,
        ServiceModuleManager $serviceModuleManager,
        CurrentPage $currentPage,
        Heart $heart
    ) {
        parent::__construct($template, $translationManager);

        $this->priceTextService = $priceTextService;
        $this->transactionRepository = $transactionRepository;
        $this->auth = $auth;
        $this->db = $db;
        $this->paginationService = $paginationService;
        $this->currentPage = $currentPage;
        $this->heart = $heart;
        $this->serviceModuleManager = $serviceModuleManager;
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
                "WHERE t.uid = ? " .
                "ORDER BY t.timestamp DESC " .
                "LIMIT ?, ?"
        );
        $statement->execute(
            array_merge([$user->getUid()], get_row_limit($this->currentPage->getPageNumber(), 10))
        );
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
                $service = $this->heart->getService($transaction->getServiceId());
                $server = $this->heart->getServer($transaction->getServerId());
                $desc = $this->lang->t(
                    "service_was_bought",
                    $service ? $service->getName() : "",
                    $server ? $server->getName() : ""
                );
                $class = "outcome";
            }

            $paymentLogs .= $this->template->render(
                "payment_log_brick",
                compact("class", "date", "cost", "desc")
            );
        }

        $paginationContent = $this->paginationService->createPagination(
            $rowsCount,
            $this->currentPage->getPageNumber(),
            $request->getPathInfo(),
            $request->query->all(),
            10
        );
        $paginationClass = $paginationContent ? "" : "display_none";

        return $this->template->render(
            "payment_log",
            compact("paymentLogs", "paginationClass", "paginationContent")
        );
    }
}
