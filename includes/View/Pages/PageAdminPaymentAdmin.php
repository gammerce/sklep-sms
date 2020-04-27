<?php
namespace App\View\Pages;

use App\Models\Transaction;
use App\Repositories\TransactionRepository;
use App\Support\Database;
use App\Support\QueryParticle;
use App\Support\Template;
use App\Translation\TranslationManager;
use App\View\CurrentPage;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\DateCell;
use App\View\Html\HeadCell;
use App\View\Html\PlatformCell;
use App\View\Html\Structure;
use App\View\Html\UserRef;
use App\View\Html\Wrapper;
use Symfony\Component\HttpFoundation\Request;

class PageAdminPaymentAdmin extends PageAdmin
{
    const PAGE_ID = "payment_admin";

    /** @var TransactionRepository */
    private $transactionRepository;

    /** @var Database */
    private $db;

    /** @var CurrentPage */
    private $currentPage;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        TransactionRepository $transactionRepository,
        Database $db,
        CurrentPage $currentPage
    ) {
        parent::__construct($template, $translationManager);

        $this->transactionRepository = $transactionRepository;
        $this->db = $db;
        $this->currentPage = $currentPage;
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("payments_admin");
    }

    public function getContent(Request $request)
    {
        $recordId = $request->query->get("record");
        $search = $request->query->get("search");

        $queryParticle = new QueryParticle();
        $queryParticle->add("t.payment = 'admin'");

        if (strlen($recordId)) {
            $queryParticle->add("AND `payment_id` = ?", [$recordId]);
        } elseif (strlen($search)) {
            $queryParticle->add("AND");
            $queryParticle->extend(
                create_search_query(
                    ["t.payment_id", "t.external_payment_id", "t.cost", "t.income", "t.ip"],
                    $search
                )
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
        $rowsCount = $this->db->query("SELECT FOUND_ROWS()")->fetchColumn();

        $bodyRows = collect($statement)
            ->map(function (array $row) {
                return $this->transactionRepository->mapToModel($row);
            })
            ->map(function (Transaction $transaction) use ($recordId) {
                $adminEntry = $transaction->getAdminId()
                    ? new UserRef($transaction->getAdminId(), $transaction->getAdminName())
                    : $this->lang->t("none");

                return (new BodyRow())
                    ->setDbId($transaction->getPaymentId())
                    ->addCell(new Cell($adminEntry))
                    ->addCell(new Cell($transaction->getIp()))
                    ->addCell(new PlatformCell($transaction->getPlatform()))
                    ->addCell(new DateCell($transaction->getTimestamp()))
                    ->when($recordId == $transaction->getPaymentId(), function (BodyRow $bodyRow) {
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
            ->enablePagination($this->getPagePath(), $request->query->all(), $rowsCount);

        return (new Wrapper())
            ->setTitle($this->getTitle($request))
            ->enableSearch()
            ->setTable($table)
            ->toHtml();
    }
}
