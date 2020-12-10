<?php
namespace App\View\Pages\Admin;

use App\Managers\ServerManager;
use App\ServiceModules\ExtraFlags\PlayerFlag;
use App\ServiceModules\ExtraFlags\PlayerFlagRepository;
use App\Support\Database;
use App\Support\Template;
use App\Translation\TranslationManager;
use App\User\Permission;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\ExpirationCell;
use App\View\Html\HeadCell;
use App\View\Html\NoneText;
use App\View\Html\ServerRef;
use App\View\Html\Structure;
use App\View\Html\Wrapper;
use App\View\Pagination\PaginationFactory;
use Symfony\Component\HttpFoundation\Request;

class PageAdminPlayersFlags extends PageAdmin
{
    const PAGE_ID = "players_flags";

    /** @var Database */
    private $db;

    /** @var ServerManager */
    private $serverManager;

    /** @var PlayerFlagRepository */
    private $playerFlagRepository;

    /** @var PaginationFactory */
    private $paginationFactory;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        Database $db,
        ServerManager $serverManager,
        PlayerFlagRepository $playerFlagRepository,
        PaginationFactory $paginationFactory
    ) {
        parent::__construct($template, $translationManager);
        $this->db = $db;
        $this->serverManager = $serverManager;
        $this->playerFlagRepository = $playerFlagRepository;
        $this->paginationFactory = $paginationFactory;
    }

    public function getPrivilege()
    {
        return Permission::VIEW_PLAYER_FLAGS();
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("players_flags");
    }

    public function getContent(Request $request)
    {
        $pagination = $this->paginationFactory->create($request);

        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS * FROM `ss_players_flags` " .
                "ORDER BY `id` DESC " .
                "LIMIT ?, ?"
        );
        $statement->execute($pagination->getSqlLimit());
        $rowsCount = $this->db->query("SELECT FOUND_ROWS()")->fetchColumn();

        $bodyRows = collect($statement)
            ->map(function (array $row) {
                return $this->playerFlagRepository->mapToModel($row);
            })
            ->map(function (PlayerFlag $playerFlag) {
                $server = $this->serverManager->getServer($playerFlag->getServerId());
                $serverEntry = $server
                    ? new ServerRef($server->getId(), $server->getName())
                    : new NoneText();

                $bodyRow = (new BodyRow())
                    ->setDbId($playerFlag->getId())
                    ->addCell(new Cell($serverEntry))
                    ->addCell(new Cell($playerFlag->getAuthData()));

                foreach (PlayerFlag::FLAGS as $flag) {
                    $flagValue = $playerFlag->getFlag($flag);
                    $bodyRow->addCell(new ExpirationCell($flagValue));
                }

                return $bodyRow;
            })
            ->all();

        $table = (new Structure())
            ->addHeadCell(new HeadCell($this->lang->t("id"), "id"))
            ->addHeadCell(new HeadCell($this->lang->t("server")))
            ->addHeadCell(
                new HeadCell(
                    $this->lang->t("nick") .
                        "/" .
                        $this->lang->t("ip") .
                        "/" .
                        $this->lang->t("sid")
                )
            )
            ->addBodyRows($bodyRows)
            ->enablePagination($this->getPagePath(), $pagination, $rowsCount);

        foreach (PlayerFlag::FLAGS as $flag) {
            $table->addHeadCell(new HeadCell($flag));
        }

        return (new Wrapper())
            ->setTitle($this->getTitle($request))
            ->setTable($table)
            ->toHtml();
    }
}
