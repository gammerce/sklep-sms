<?php

use Admin\Table\BodyRow;
use Admin\Table\Cell;
use Admin\Table\Div;
use Admin\Table\Structure;
use Admin\Table\Wrapper;

class PageAdminLogs extends PageAdmin
{
    const PAGE_ID = "logs";
    protected $privilage = "view_logs";

    public function __construct()
    {
        global $lang;
        $this->title = $lang->translate('logs');

        parent::__construct();
    }

    protected function content($get, $post)
    {
        global $db, $lang;

        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);
        $wrapper->setSearch();

        $table = new Structure();

        $cell = new Cell($lang->translate('id'));
        $cell->setParam('headers', 'id');
        $table->addHeadCell($cell);

        $table->addHeadCell(new Cell($lang->translate('text')));
        $table->addHeadCell(new Cell($lang->translate('date')));

        // Wyszukujemy dane ktore spelniaja kryteria
        $where = '';
        if (isset($get['search'])) {
            searchWhere(["`id`", "`text`", "CAST(`timestamp` as CHAR)"], $get['search'], $where);
        }

        // Jezeli jest jakis where, to dodajemy WHERE
        if (strlen($where)) {
            $where = "WHERE " . $where . " ";
        }

        $result = $db->query(
            "SELECT SQL_CALC_FOUND_ROWS * FROM `" . TABLE_PREFIX . "logs` " .
            $where .
            "ORDER BY `id` DESC " .
            "LIMIT " . get_row_limit($this->currentPage->getPageNumber())
        );

        $table->setDbRowsAmount($db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()"));

        while ($row = $db->fetch_array_assoc($result)) {
            $body_row = new BodyRow();

            $body_row->setDbId($row['id']);

            $cell = new Cell();
            $div = new Div(htmlspecialchars($row['text']));
            $div->setParam('class', 'one_line');
            $cell->addContent($div);
            $body_row->addCell($cell);

            $cell = new Cell(convertDate($row['timestamp']));
            $cell->setParam('headers', 'date');
            $body_row->addCell($cell);

            if (get_privilages("manage_logs")) {
                $body_row->setButtonDelete(true);
            }

            $table->addBodyRow($body_row);
        }

        $wrapper->setTable($table);

        return $wrapper->toHtml();
    }
}