<?php
namespace App\Pages;

use Admin\Table\BodyRow;
use Admin\Table\Cell;
use Admin\Table\Structure;
use Admin\Table\Wrapper;

class PageAdminPlayersFlags extends PageAdmin
{
    const PAGE_ID = 'players_flags';
    protected $privilege = 'view_player_flags';

    protected $flags = 'abcdefghijklmnopqrstuyvwxz';

    public function __construct()
    {
        parent::__construct();

        $this->heart->page_title = $this->title = $this->lang->translate('players_flags');
    }

    protected function content($get, $post)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();

        $cell = new Cell($this->lang->translate('id'));
        $cell->setParam('headers', 'id');
        $table->addHeadCell($cell);

        $table->addHeadCell(new Cell($this->lang->translate('server')));
        $table->addHeadCell(
            new Cell(
                "{$this->lang->translate('nick')}/{$this->lang->translate(
                    'ip'
                )}/{$this->lang->translate('sid')}"
            )
        );
        foreach (str_split($this->flags) as $flag) {
            $table->addHeadCell(new Cell($flag));
        }

        $result = $this->db->query(
            "SELECT SQL_CALC_FOUND_ROWS * FROM `" .
                TABLE_PREFIX .
                "players_flags` " .
                "ORDER BY `id` DESC " .
                "LIMIT " .
                get_row_limit($this->currentPage->getPageNumber())
        );

        $table->setDbRowsAmount($this->db->getColumn("SELECT FOUND_ROWS()", "FOUND_ROWS()"));

        while ($row = $this->db->fetchArrayAssoc($result)) {
            $body_row = new BodyRow();

            // Pozyskanie danych serwera
            $temp_server = $this->heart->get_server($row['server']);
            $server_name = $temp_server['name'];
            unset($temp_server);

            $body_row->setDbId($row['id']);
            $body_row->addCell(new Cell($server_name));
            $body_row->addCell(new Cell(htmlspecialchars($row['auth_data'])));

            foreach (str_split($this->flags) as $flag) {
                if (!$row[$flag]) {
                    $body_row->addCell(new Cell(' '));
                } else {
                    if ($row[$flag] == -1) {
                        $body_row->addCell(new Cell($this->lang->translate('never')));
                    } else {
                        $body_row->addCell(
                            new Cell(date($this->settings['date_format'], $row[$flag]))
                        );
                    }
                }
            }

            $table->addBodyRow($body_row);
        }

        $wrapper->setTable($table);

        return $wrapper->toHtml();
    }
}
