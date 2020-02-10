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
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();
        $table->addHeadCell(new HeadCell($this->lang->t('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->t('server')));
        $table->addHeadCell(
            new HeadCell("{$this->lang->t('nick')}/{$this->lang->t('ip')}/{$this->lang->t('sid')}")
        );

        foreach (PlayerFlag::FLAGS as $flag) {
            $table->addHeadCell(new HeadCell($flag));
        }

        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS * FROM `ss_players_flags` " .
                "ORDER BY `id` DESC " .
                "LIMIT ?, ?"
        );
        $statement->execute(get_row_limit($this->currentPage->getPageNumber()));

        $table->setDbRowsCount($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

        foreach ($statement as $row) {
            $bodyRow = new BodyRow();

            $tempServer = $this->heart->getServer($row['server']);
            $serverName = $tempServer->getName();
            unset($tempServer);

            $bodyRow->setDbId($row['id']);
            $bodyRow->addCell(new Cell($serverName));
            $bodyRow->addCell(new Cell($row['auth_data']));

            foreach (PlayerFlag::FLAGS as $flag) {
                if (!$row[$flag]) {
                    $bodyRow->addCell(new Cell(' '));
                } else {
                    $bodyRow->addCell(new Cell(convert_expire($row[$flag])));
                }
            }

            $table->addBodyRow($bodyRow);
        }

        $wrapper->setTable($table);

        return $wrapper->toHtml();
    }
}
