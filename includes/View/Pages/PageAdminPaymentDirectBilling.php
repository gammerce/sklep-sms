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

class PageAdminPaymentDirectBilling extends PageAdmin
{
    const PAGE_ID = "payment_direct_billing";

    /** @var PriceTextService */
    private $priceTextService;

    /** @var TransactionRepository */
    private $transactionRepository;

    public function __construct(
        PriceTextService $priceTextService,
        TransactionRepository $transactionRepository
    ) {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t("payments_direct_billing");
        $this->priceTextService = $priceTextService;
        $this->transactionRepository = $transactionRepository;
    }

    protected function content(array $query, array $body)
    {
        $queryParticle = new QueryParticle();
        $queryParticle->add("( t.payment = 'direct_billing' )");

        if (isset($query["search"])) {
            $queryParticle->extend(
                create_search_query(
                    ["t.payment_id", "t.external_payment_id", "t.cost", "t.income", "t.ip"],
                    $query["search"]
                )
            );
        }

        if (isset($query["payid"])) {
            $queryParticle->add("AND `payment_id` = ?", [$query["payid"]]);
        }

        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS * " .
                "FROM ({$this->transactionRepository->getQuery()}) as t " .
                "WHERE $queryParticle " .
                "ORDER BY t.id DESC " .
                "LIMIT ?, ?"
        );
        $statement->execute(
            array_merge(
                $queryParticle->params(),
                get_row_limit($this->currentPage->getPageNumber())
            )
        );
        $rowsCount = $this->db->query("SELECT FOUND_ROWS()")->fetchColumn();

        $bodyRows = collect($statement)
            ->map(function (array $row) {
                return $this->transactionRepository->mapToModel($row);
            })
            ->map(function (Transaction $transaction) use ($query) {
                $income = $this->priceTextService->getPriceText($transaction->getIncome());
                $cost = $this->priceTextService->getPriceText($transaction->getCost());

                $bodyRow = (new BodyRow())
                    ->setDbId($transaction->getPaymentId())
                    ->addCell(new Cell($transaction->getExternalPaymentId()))
                    ->addCell(new Cell($income))
                    ->addCell(new Cell($cost))
                    ->addCell(new Cell($transaction->getIp()))
                    ->addCell(new PlatformCell($transaction->getPlatform()))
                    ->addCell(new DateCell($transaction->getTimestamp()));

                if ($query["payid"] == $transaction->getPaymentId()) {
                    $bodyRow->addClass("highlighted");
                }

                return $bodyRow;
            })
            ->all();

        $table = (new Structure())
            ->addHeadCell(new HeadCell($this->lang->t("id"), "id"))
            ->addHeadCell(new HeadCell($this->lang->t("external_id")))
            ->addHeadCell(new HeadCell($this->lang->t("income")))
            ->addHeadCell(new HeadCell($this->lang->t("cost")))
            ->addHeadCell(new HeadCell($this->lang->t("ip")))
            ->addHeadCell(new HeadCell($this->lang->t("platform"), "platform"))
            ->addHeadCell(new HeadCell($this->lang->t("date")))
            ->addBodyRows($bodyRows)
            ->setDbRowsCount($rowsCount);

        return (new Wrapper())
            ->setTitle($this->title)
            ->setTable($table)
            ->toHtml();
    }
}
