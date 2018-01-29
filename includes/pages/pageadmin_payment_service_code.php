<?php

use Admin\Table\BodyRow;
use Admin\Table\Cell;
use Admin\Table\Div;
use Admin\Table\Structure;
use Admin\Table\Wrapper;

class PageAdminPaymentServiceCode extends PageAdmin
{
    const PAGE_ID = 'payment_service_code';

    public function __construct()
    {
        parent::__construct();

        $this->heart->page_title = $this->title = $this->lang->translate('payments_service_code');
    }

    protected function content($get, $post)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();

        $cell = new Cell($this->lang->translate('id'));
        $cell->setParam('headers', 'id');
        $table->addHeadCell($cell);

        $table->addHeadCell(new Cell($this->lang->translate('code')));
        $table->addHeadCell(new Cell($this->lang->translate('ip')));

        $cell = new Cell($this->lang->translate('platform'));
        $cell->setParam('headers', 'platform');
        $table->addHeadCell($cell);

        $table->addHeadCell(new Cell($this->lang->translate('date')));

        $where = "";
        if (isset($get['payid'])) {
            $where .= $this->db->prepare(" AND `payment_id` = '%d' ", [$get['payid']]);
        }

        $result = $this->db->query(
            "SELECT SQL_CALC_FOUND_ROWS * " .
            "FROM ({$this->settings['transactions_query']}) as t " .
            "WHERE t.payment = 'service_code' " . $where .
            "ORDER BY t.timestamp DESC " .
            "LIMIT " . get_row_limit($this->currentPage->getPageNumber())
        );

        $table->setDbRowsAmount($this->db->get_column('SELECT FOUND_ROWS()', 'FOUND_ROWS()'));

        while ($row = $this->db->fetch_array_assoc($result)) {
            $body_row = new BodyRow();

            if ($get['highlight'] && $get['payid'] == $row['payment_id']) {
                $body_row->setParam('class', 'highlighted');
            }

            $body_row->setDbId($row['payment_id']);
            $body_row->addCell(new Cell($row['service_code']));
            $body_row->addCell(new Cell(htmlspecialchars($row['ip'])));

            $cell = new Cell();
            $div = new Div(get_platform($row['platform']));
            $div->setParam('class', 'one_line');
            $cell->addContent($div);
            $body_row->addCell($cell);

            $body_row->addCell(new Cell(convertDate($row['timestamp'])));

            $table->addBodyRow($body_row);
        }

        $wrapper->setTable($table);

        return $wrapper->toHtml();
    }
}
