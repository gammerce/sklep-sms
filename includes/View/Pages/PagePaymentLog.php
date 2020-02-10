<?php
namespace App\View\Pages;

use App\Repositories\TransactionRepository;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\Services\PriceTextService;
use App\System\Auth;
use App\View\Interfaces\IBeLoggedMust;
use App\View\Pagination;
use Symfony\Component\HttpFoundation\Request;

class PagePaymentLog extends Page implements IBeLoggedMust
{
    const PAGE_ID = 'payment_log';

    /** @var PriceTextService */
    private $priceTextService;

    /** @var TransactionRepository */
    private $transactionRepository;

    public function __construct(
        PriceTextService $priceTextService,
        TransactionRepository $transactionRepository
    ) {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t('payment_log');
        $this->priceTextService = $priceTextService;
        $this->transactionRepository = $transactionRepository;
    }

    protected function content(array $query, array $body)
    {
        /** @var Auth $auth */
        $auth = $this->app->make(Auth::class);
        $user = $auth->user();

        /** @var Request $request */
        $request = $this->app->make(Request::class);

        /** @var Pagination $pagination */
        $pagination = $this->app->make(Pagination::class);

        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS * FROM ({$this->transactionRepository->getQuery()}) as t " .
                "WHERE t.uid = ? " .
                "ORDER BY t.timestamp DESC " .
                "LIMIT ?"
        );
        $statement->execute([
            $user->getUid(),
            get_row_limit($this->currentPage->getPageNumber(), 10),
        ]);
        $rowsCount = $this->db->query("SELECT FOUND_ROWS()")->fetchColumn();

        $paymentLogs = "";
        foreach ($statement as $row) {
            $transaction = $this->transactionRepository->mapToModel($row);
            $date = $transaction->getTimestamp();
            $cost = $this->priceTextService->getPriceText($transaction->getCost());

            $serviceModule = $this->heart->getServiceModule($transaction->getServiceId());
            if ($serviceModule instanceof IServicePurchaseWeb) {
                $logInfo = $serviceModule->purchaseInfo("payment_log", $transaction);
                $desc = $logInfo['text'];
                $class = $logInfo['class'];
            } else {
                $service = $this->heart->getService($transaction->getServiceId());
                $server = $this->heart->getServer($transaction->getServerId());
                $desc = $this->lang->t(
                    'service_was_bought',
                    $service ? $service->getName() : '',
                    $server ? $server->getName() : ''
                );
                $class = "outcome";
            }

            $paymentLogs .= $this->template->render(
                "payment_log_brick",
                compact('class', 'date', 'cost', 'desc')
            );
        }

        $paginationContent = $pagination->getPagination(
            $rowsCount,
            $this->currentPage->getPageNumber(),
            $request->getPathInfo(),
            $query,
            10
        );
        $paginationClass = $paginationContent ? "" : "display_none";

        return $this->template->render(
            "payment_log",
            compact('paymentLogs', 'paginationClass', 'paginationContent')
        );
    }
}
