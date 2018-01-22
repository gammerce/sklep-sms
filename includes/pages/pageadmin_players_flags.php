<?php

use Admin\Table\BodyRow;
use Admin\Table\Cell;
use Admin\Table\Structure;
use Admin\Table\Wrapper;

class PageAdminPlayersFlags extends PageAdmin
{
    const PAGE_ID = 'players_flags';
    protected $privilage = 'view_player_flags';

    protected $flags = 'abcdefghijklmnopqrstuyvwxz';

    public function __construct()
    {
        parent::__construct();

        $this->heart->page_title = $this->title = $this->lang->translate('players_flags');
    }

    protected function content($get, $post)
    {
        global $heart, $db, $settings, $lang;

        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();

        $cell = new Cell($lang->translate('id'));
        $cell->setParam('headers', 'id');
        $table->addHeadCell($cell);

        $table->addHeadCell(new Cell($lang->translate('server')));
        $table->addHeadCell(new Cell("{$lang->translate('nick')}/{$lang->translate('ip')}/{$lang->translate('sid')}"));
        foreach (str_split($this->flags) as $flag) {
            $table->addHeadCell(new Cell($flag));
        }

        $result = $db->query(
            "SELECT SQL_CALC_FOUND_ROWS * FROM `" . TABLE_PREFIX . "players_flags` " .
            "ORDER BY `id` DESC " .
            "LIMIT " . get_row_limit($this->currentPage->getPageNumber())
        );

        $table->setDbRowsAmount($db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()"));

        while ($row = $db->fetch_array_assoc($result)) {
            $body_row = new BodyRow();

            // Pozyskanie danych serwera
            $temp_server = $heart->get_server($row['server']);
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
                        $body_row->addCell(new Cell($lang->translate('never')));
                    } else {
                        $body_row->addCell(new Cell(date($settings['date_format'], $row[$flag])));
                    }
                }
            }

            $table->addBodyRow($body_row);
        }

        $wrapper->setTable($table);

        return $wrapper->toHtml();
    }
}