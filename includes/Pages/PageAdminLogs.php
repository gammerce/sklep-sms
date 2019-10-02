<?php
namespace App\Pages;

use Admin\Table\BodyRow;
use Admin\Table\Cell;
use Admin\Table\Div;
use Admin\Table\HeadCell;
use Admin\Table\Structure;
use Admin\Table\Wrapper;

class PageAdminLogs extends PageAdmin
{
    const PAGE_ID = 'logs';
    protected $privilege = 'view_logs';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->translate('logs');
    }

    protected function content(array $query, array $body)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);
        $wrapper->setSearch();

        $table = new Structure();
        $table->addHeadCell(new HeadCell($this->lang->translate('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->translate('text')));
        $table->addHeadCell(new HeadCell($this->lang->translate('date')));

        // Wyszukujemy dane ktore spelniaja kryteria
        $where = '';
        if (isset($query['search'])) {
            searchWhere(["`id`", "`text`", "CAST(`timestamp` as CHAR)"], $query['search'], $where);
        }

        // Jezeli jest jakis where, to dodajemy WHERE
        if (strlen($where)) {
            $where = "WHERE " . $where . " ";
        }

        $result = $this->db->query(
            "SELECT SQL_CALC_FOUND_ROWS * FROM `" .
                TABLE_PREFIX .
                "logs` " .
                $where .
                "ORDER BY `id` DESC " .
                "LIMIT " .
                get_row_limit($this->currentPage->getPageNumber())
        );

        $table->setDbRowsAmount($this->db->getColumn("SELECT FOUND_ROWS()", "FOUND_ROWS()"));

        while ($row = $this->db->fetchArrayAssoc($result)) {
            $bodyRow = new BodyRow();

            $bodyRow->setDbId($row['id']);

            $cell = new Cell();
            $div = new Div(htmlspecialchars($row['text']));
            $div->setParam('class', 'one_line');
            $cell->addContent($div);
            $bodyRow->addCell($cell);

            $cell = new Cell(convertDate($row['timestamp']));
            $cell->setParam('headers', 'date');
            $bodyRow->addCell($cell);

            if (get_privileges("manage_logs")) {
                $bodyRow->setDeleteAction(true);
            }

            $table->addBodyRow($bodyRow);
        }

        $wrapper->setTable($table);

        return $wrapper->toHtml();
    }
}
