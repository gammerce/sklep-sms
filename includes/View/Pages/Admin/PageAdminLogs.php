<?php
namespace App\View\Pages\Admin;

use App\Support\Database;
use App\Support\QueryParticle;
use App\Theme\Template;
use App\Translation\TranslationManager;
use App\User\Permission;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\DateTimeCell;
use App\View\Html\Div;
use App\View\Html\HeadCell;
use App\View\Html\Structure;
use App\View\Html\Wrapper;
use App\View\Pagination\PaginationFactory;
use Symfony\Component\HttpFoundation\Request;

class PageAdminLogs extends PageAdmin
{
    const PAGE_ID = "logs";

    private Database $db;
    private PaginationFactory $paginationFactory;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        Database $db,
        PaginationFactory $paginationFactory
    ) {
        parent::__construct($template, $translationManager);
        $this->db = $db;
        $this->paginationFactory = $paginationFactory;
    }

    public function getPrivilege(): Permission
    {
        return Permission::LOGS_VIEW();
    }

    public function getTitle(Request $request = null): string
    {
        return $this->lang->t("logs");
    }

    public function getContent(Request $request)
    {
        $search = $request->query->get("search");

        $pagination = $this->paginationFactory->create($request);
        $queryParticle = new QueryParticle();

        if ($search) {
            $queryParticle->extend(
                create_search_query(["`id`", "`text`", "CAST(`timestamp` as CHAR)"], $search)
            );
        }

        $where = $queryParticle->isEmpty() ? "" : "WHERE {$queryParticle}";

        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS * FROM `ss_logs` {$where} ORDER BY `id` DESC LIMIT ?, ?"
        );
        $statement->bindAndExecute(
            array_merge($queryParticle->params(), $pagination->getSqlLimit())
        );
        $rowsCount = $this->db->query("SELECT FOUND_ROWS()")->fetchColumn();

        $bodyRows = collect($statement)
            ->map(function (array $row) {
                $div = new Div($row["text"]);
                $div->addClass("one-line");

                return (new BodyRow())
                    ->setDbId($row["id"])
                    ->addCell(new Cell($div))
                    ->addCell(new DateTimeCell($row["timestamp"]))
                    ->setDeleteAction(can(Permission::LOGS_MANAGEMENT()));
            })
            ->all();

        $table = (new Structure())
            ->addHeadCell(new HeadCell($this->lang->t("id"), "id"))
            ->addHeadCell(new HeadCell($this->lang->t("text")))
            ->addHeadCell(new HeadCell($this->lang->t("date")))
            ->addBodyRows($bodyRows)
            ->enablePagination($this->getPagePath(), $pagination, $rowsCount);

        return (new Wrapper())
            ->setTitle($this->getTitle($request))
            ->enableSearch()
            ->setTable($table)
            ->toHtml();
    }
}
