<?php
namespace App\View\Pages\Admin;

use App\ServiceModules\ExtraFlags\PlayerFlag;
use App\Support\Database;
use App\Support\Template;
use App\System\Heart;
use App\Translation\TranslationManager;
use App\View\CurrentPage;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\HeadCell;
use App\View\Html\ServerRef;
use App\View\Html\Structure;
use App\View\Html\Wrapper;
use Symfony\Component\HttpFoundation\Request;

class PageAdminPlayersFlags extends PageAdmin
{
    const PAGE_ID = "players_flags";

    /** @var Database */
    private $db;

    /** @var CurrentPage */
    private $currentPage;

    /** @var Heart */
    private $heart;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        Database $db,
        CurrentPage $currentPage,
        Heart $heart
    ) {
        parent::__construct($template, $translationManager);
        $this->db = $db;
        $this->currentPage = $currentPage;
        $this->heart = $heart;
    }

    public function getPrivilege()
    {
        return "view_player_flags";
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("players_flags");
    }

    public function getContent(Request $request)
    {
        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS * FROM `ss_players_flags` " .
                "ORDER BY `id` DESC " .
                "LIMIT ?, ?"
        );
        $statement->execute(get_row_limit($this->currentPage->getPageNumber()));
        $rowsCount = $this->db->query("SELECT FOUND_ROWS()")->fetchColumn();

        $bodyRows = collect($statement)
            ->map(function (array $row) {
                $server = $this->heart->getServer($row["server"]);

                $bodyRow = (new BodyRow())
                    ->setDbId($row["id"])
                    ->addCell(new Cell(new ServerRef($server->getId(), $server->getName())))
                    ->addCell(new Cell($row["auth_data"]));

                foreach (PlayerFlag::FLAGS as $flag) {
                    $value = $row[$flag] ? convert_expire($row[$flag]) : " ";
                    $bodyRow->addCell(new Cell($value));
                }

                return $bodyRow;
            })
            ->all();

        $table = (new Structure())
            ->addHeadCell(new HeadCell($this->lang->t("id"), "id"))
            ->addHeadCell(new HeadCell($this->lang->t("server")))
            ->addHeadCell(
                new HeadCell(
                    "{$this->lang->t("nick")}/{$this->lang->t("ip")}/{$this->lang->t("sid")}"
                )
            )
            ->addBodyRows($bodyRows)
            ->enablePagination($this->getPagePath(), $request->query->all(), $rowsCount);

        foreach (PlayerFlag::FLAGS as $flag) {
            $table->addHeadCell(new HeadCell($flag));
        }

        return (new Wrapper())
            ->setTitle($this->getTitle($request))
            ->setTable($table)
            ->toHtml();
    }
}