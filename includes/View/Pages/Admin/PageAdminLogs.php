<?php
namespace App\View\Pages\Admin;

use App\Support\Database;
use App\Support\QueryParticle;
use App\Support\Template;
use App\Translation\TranslationManager;
use App\View\CurrentPage;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\Div;
use App\View\Html\HeadCell;
use App\View\Html\Structure;
use App\View\Html\Wrapper;
use Symfony\Component\HttpFoundation\Request;

class PageAdminLogs extends PageAdmin
{
    const PAGE_ID = "logs";

    /** @var Database */
    private $db;

    /** @var CurrentPage */
    private $currentPage;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        Database $db,
        CurrentPage $currentPage
    ) {
        parent::__construct($template, $translationManager);
        $this->db = $db;
        $this->currentPage = $currentPage;
    }

    public function getPrivilege()
    {
        return "view_logs";
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("logs");
    }

    public function getContent(Request $request)
    {
        $search = $request->query->get("search");

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
        $statement->execute(
            array_merge(
                $queryParticle->params(),
                get_row_limit($this->currentPage->getPageNumber())
            )
        );
        $rowsCount = $this->db->query("SELECT FOUND_ROWS()")->fetchColumn();

        $bodyRows = collect($statement)
            ->map(function (array $row) {
                $div = new Div($row["text"]);
                $div->addClass("one_line");

                return (new BodyRow())
                    ->setDbId($row["id"])
                    ->addCell(new Cell($div))
                    ->addCell(new Cell(convert_date($row["timestamp"]), "date"))
                    ->setDeleteAction(has_privileges("manage_logs"));
            })
            ->all();

        $table = (new Structure())
            ->addHeadCell(new HeadCell($this->lang->t("id"), "id"))
            ->addHeadCell(new HeadCell($this->lang->t("text")))
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
