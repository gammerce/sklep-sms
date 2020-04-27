<?php
namespace App\View\Pages;

use App\Models\Transaction;
use App\Repositories\TransactionRepository;
use App\Services\PriceTextService;
use App\Support\QueryParticle;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\DateCell;
use App\View\Html\HeadCell;
use App\View\Html\PlatformCell;
use App\View\Html\Structure;
use App\View\Html\Wrapper;
use Symfony\Component\HttpFoundation\Request;

class PageAdminPaymentTransfer extends PageAdmin
{
    const PAGE_ID = 'payment_transfer';

    /** @var PriceTextService */
    private $priceTextService;

    /** @var TransactionRepository */
    private $transactionRepository;

    public function __construct(
        PriceTextService $priceTextService,
        TransactionRepository $transactionRepository
    ) {
        parent::__construct();

        $this->priceTextService = $priceTextService;
        $this->transactionRepository = $transactionRepository;
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("payments_transfer");
    }

    protected function content(array $query, array $body)
    {
        $recordId = array_get($query, "record");
        $search = array_get($query, "search");

        $queryParticle = new QueryParticle();
        $queryParticle->add("( t.payment = 'transfer' )");

        if (strlen($recordId)) {
            $queryParticle->add("AND `payment_id` = ?", [$recordId]);
        } elseif (strlen($search)) {
            $queryParticle->add("AND");
            $queryParticle->extend(
                create_search_query(["t.payment_id", "t.income", "t.ip"], $search)
            );
        }

        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS * " .
                "FROM ({$this->transactionRepository->getQuery()}) as t " .
                "WHERE $queryParticle " .
                "ORDER BY t.timestamp DESC " .
                "LIMIT ?, ?"
        );
        $statement->execute(
            array_merge(
                $queryParticle->params(),
                get_row_limit($this->currentPage->getPageNumber())
            )
        );
        $rowsCount = $this->db->query('SELECT FOUND_ROWS()')->fetchColumn();

        $bodyRows = collect($statement)
            ->map(function (array $row) {
                return $this->transactionRepository->mapToModel($row);
            })
            ->map(function (Transaction $transaction) use ($recordId) {
                $income = $this->priceTextService->getPriceText($transaction->getIncome());

                return (new BodyRow())
                    ->setDbId($transaction->getPaymentId())
                    ->addCell(new Cell($income))
                    ->addCell(new Cell($transaction->getIp()))
                    ->addCell(new PlatformCell($transaction->getPlatform()))
                    ->addCell(new DateCell($transaction->getTimestamp()))
                    ->when($recordId == $transaction->getPaymentId(), function (BodyRow $bodyRow) {
                        $bodyRow->addClass('highlighted');
                    });
            })
            ->all();

        $table = (new Structure())
            ->addHeadCell(new HeadCell($this->lang->t('id'), "id"))
            ->addHeadCell(new HeadCell($this->lang->t('cost')))
            ->addHeadCell(new HeadCell($this->lang->t('ip')))
            ->addHeadCell(new HeadCell($this->lang->t('platform'), "platform"))
            ->addHeadCell(new HeadCell($this->lang->t('date')))
            ->addBodyRows($bodyRows)
            ->enablePagination($this->getPagePath(), $query, $rowsCount);

        return (new Wrapper())
            ->setTitle($this->title)
            ->enableSearch()
            ->setTable($table)
            ->toHtml();
    }
}
