<?php
namespace App\View\Pages;

use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\HeadCell;
use App\View\Html\Structure;
use App\View\Html\Wrapper;

class PageAdminPlayersFlags extends PageAdmin
{
    const PAGE_ID = 'players_flags';
    protected $privilege = 'view_player_flags';

    protected $flags = 'abcdefghijklmnopqrstuyvwxz';

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

        foreach (str_split($this->flags) as $flag) {
            $table->addHeadCell(new HeadCell($flag));
        }

        $result = $this->db->query(
            "SELECT SQL_CALC_FOUND_ROWS * FROM `ss_players_flags` " .
                "ORDER BY `id` DESC " .
                "LIMIT " .
                get_row_limit($this->currentPage->getPageNumber())
        );

        $table->setDbRowsCount($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

        foreach ($result as $row) {
            $bodyRow = new BodyRow();

            // Pozyskanie danych serwera
            $tempServer = $this->heart->getServer($row['server']);
            $serverName = $tempServer->getName();
            unset($tempServer);

            $bodyRow->setDbId($row['id']);
            $bodyRow->addCell(new Cell($serverName));
            $bodyRow->addCell(new Cell($row['auth_data']));

            foreach (str_split($this->flags) as $flag) {
                if (!$row[$flag]) {
                    $bodyRow->addCell(new Cell(' '));
                } else {
                    if ($row[$flag] == -1) {
                        $bodyRow->addCell(new Cell($this->lang->t('never')));
                    } else {
                        $bodyRow->addCell(
                            new Cell(date($this->settings->getDateFormat(), $row[$flag]))
                        );
                    }
                }
            }

            $table->addBodyRow($bodyRow);
        }

        $wrapper->setTable($table);

        return $wrapper->toHtml();
    }
}
