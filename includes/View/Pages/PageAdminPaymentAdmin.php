<?php
namespace App\View\Pages;

use App\Models\Transaction;
use App\Repositories\TransactionRepository;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\DateCell;
use App\View\Html\HeadCell;
use App\View\Html\PlatformCell;
use App\View\Html\Structure;
use App\View\Html\Wrapper;

class PageAdminPaymentAdmin extends PageAdmin
{
    const PAGE_ID = "payment_admin";

    /** @var TransactionRepository */
    private $transactionRepository;

    public function __construct(TransactionRepository $transactionRepository)
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t("payments_admin");
        $this->transactionRepository = $transactionRepository;
    }

    protected function content(array $query, array $body)
    {
        $recordId = as_int(array_get($query, "record"));

        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS * " .
                "FROM ({$this->transactionRepository->getQuery()}) as t " .
                "WHERE t.payment = 'admin' " .
                "ORDER BY t.timestamp DESC " .
                "LIMIT ?, ?"
        );
        $statement->execute(get_row_limit($this->currentPage->getPageNumber()));
        $rowsCount = $this->db->query("SELECT FOUND_ROWS()")->fetchColumn();

        $bodyRows = collect($statement)
            ->map(function (array $row) {
                return $this->transactionRepository->mapToModel($row);
            })
            ->map(function (Transaction $transaction) use ($recordId) {
                $adminName = $transaction->getAdminId()
                    ? "{$transaction->getAdminName()} ({$transaction->getAdminId()})"
                    : $this->lang->t("none");

                return (new BodyRow())
                    ->setDbId($transaction->getId())
                    ->addCell(new Cell($adminName))
                    ->addCell(new Cell($transaction->getIp()))
                    ->addCell(new PlatformCell($transaction->getPlatform()))
                    ->addCell(new DateCell($transaction->getTimestamp()))
                    ->when($recordId === $transaction->getPaymentId(), function (BodyRow $bodyRow) {
                        $bodyRow->addClass('highlighted');
                    });
            })
            ->all();

        $table = (new Structure())
            ->addHeadCell(new HeadCell($this->lang->t("id"), "id"))
            ->addHeadCell(new HeadCell($this->lang->t("admin_id")))
            ->addHeadCell(new HeadCell($this->lang->t("ip")))
            ->addHeadCell(new HeadCell($this->lang->t("platform"), "platform"))
            ->addHeadCell(new HeadCell($this->lang->t("date")))
            ->addBodyRows($bodyRows)
            ->enablePagination($this->getPagePath(), $query, $rowsCount);

        return (new Wrapper())
            ->setTitle($this->title)
            ->setTable($table)
            ->toHtml();
    }
}
