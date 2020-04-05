<?php
namespace App\View\Pages;

use App\ServiceModules\ExtraFlags\PlayerFlag;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\HeadCell;
use App\View\Html\Structure;
use App\View\Html\Wrapper;

class PageAdminPlayersFlags extends PageAdmin
{
    const PAGE_ID = 'players_flags';
    protected $privilege = 'view_player_flags';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t('players_flags');
    }

    protected function content(array $query, array $body)
    {
        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS * FROM `ss_players_flags` " .
                "ORDER BY `id` DESC " .
                "LIMIT ?, ?"
        );
        $statement->execute(get_row_limit($this->currentPage->getPageNumber()));
        $rowsCount = $this->db->query('SELECT FOUND_ROWS()')->fetchColumn();

        $bodyRows = collect($statement)
            ->map(function (array $row) {
                $server = $this->heart->getServer($row['server']);

                $bodyRow = (new BodyRow())
                    ->setDbId($row['id'])
                    ->addCell(new Cell($server->getName()))
                    ->addCell(new Cell($row['auth_data']));

                foreach (PlayerFlag::FLAGS as $flag) {
                    $value = $row[$flag] ? convert_expire($row[$flag]) : " ";
                    $bodyRow->addCell(new Cell($value));
                }

                return $bodyRow;
            })
            ->all();

        $table = (new Structure())
            ->addHeadCell(new HeadCell($this->lang->t('id'), "id"))
            ->addHeadCell(new HeadCell($this->lang->t('server')))
            ->addHeadCell(
                new HeadCell("{$this->lang->t('nick')}/{$this->lang->t('ip')}/{$this->lang->t('sid')}")
            )
            ->addBodyRows($bodyRows)
            ->enablePagination($this->getPagePath(), $query, $rowsCount);

        foreach (PlayerFlag::FLAGS as $flag) {
            $table->addHeadCell(new HeadCell($flag));
        }

        return (new Wrapper())
            ->setTitle($this->title)
            ->setTable($table)
            ->toHtml();
    }
}
