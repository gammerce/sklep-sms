<?php
namespace App\Pages;

use Admin\Table\BodyRow;
use Admin\Table\Cell;
use Admin\Table\Div;
use Admin\Table\Structure;
use Admin\Table\Wrapper;

class PageAdminPaymentSms extends PageAdmin
{
    const PAGE_ID = 'payment_sms';

    public function __construct()
    {
        parent::__construct();

        $this->heart->page_title = $this->title = $this->lang->translate('payments_sms');
    }

    protected function content($get, $post)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();

        $cell = new Cell($this->lang->translate('id'));
        $cell->setParam('headers', 'id');
        $table->addHeadCell($cell);

        $table->addHeadCell(new Cell($this->lang->translate('content')));
        $table->addHeadCell(new Cell($this->lang->translate('number')));
        $table->addHeadCell(new Cell($this->lang->translate('sms_return_code')));
        $table->addHeadCell(new Cell($this->lang->translate('income')));
        $table->addHeadCell(new Cell($this->lang->translate('cost')));
        $table->addHeadCell(new Cell($this->lang->translate('free_of_charge')));
        $table->addHeadCell(new Cell($this->lang->translate('ip')));

        $cell = new Cell($this->lang->translate('platform'));
        $cell->setParam('headers', 'platform');
        $table->addHeadCell($cell);

        $table->addHeadCell(new Cell($this->lang->translate('date')));

        $where = "( t.payment = 'sms' ) ";

        // Wyszukujemy platnosci o konkretnym ID
        if (isset($get['payid'])) {
            if (strlen($where)) {
                $where .= " AND ";
            }

            $where .= $this->db->prepare("( t.payment_id = '%s' ) ", [$get['payid']]);

            // Podświetlenie konkretnej płatności
            //$row['class'] = "highlighted";
        }
        // Wyszukujemy dane ktore spelniaja kryteria
        else {
            if (isset($get['search'])) {
                searchWhere(
                    ["t.payment_id", "t.sms_text", "t.sms_code", "t.sms_number"],
                    $get['search'],
                    $where
                );
            }
        }

        if (isset($get['payid'])) {
            $where .= $this->db->prepare(" AND `payment_id` = '%d' ", [$get['payid']]);
        }

        // Jezeli jest jakis where, to dodajemy WHERE
        if (strlen($where)) {
            $where = "WHERE " . $where . " ";
        }

        $result = $this->db->query(
            "SELECT SQL_CALC_FOUND_ROWS * " .
                "FROM ({$this->settings['transactions_query']}) as t " .
                $where .
                "ORDER BY t.timestamp DESC " .
                "LIMIT " .
                get_row_limit($this->currentPage->getPageNumber())
        );

        $table->setDbRowsAmount($this->db->getColumn('SELECT FOUND_ROWS()', 'FOUND_ROWS()'));

        while ($row = $this->db->fetchArrayAssoc($result)) {
            $body_row = new BodyRow();

            if ($get['highlight'] && $get['payid'] == $row['payment_id']) {
                $body_row->setParam('class', 'highlighted');
            }

            $free = $row['free']
                ? $this->lang->strtoupper($this->lang->translate('yes'))
                : $this->lang->strtoupper($this->lang->translate('no'));
            $income = $row['income']
                ? number_format($row['income'] / 100.0, 2) . " " . $this->settings['currency']
                : "";
            $cost = $row['cost']
                ? number_format($row['cost'] / 100.0, 2) . " " . $this->settings['currency']
                : "";

            $body_row->setDbId($row['payment_id']);
            $body_row->addCell(new Cell($row['sms_text']));
            $body_row->addCell(new Cell($row['sms_number']));
            $body_row->addCell(new Cell($row['sms_code']));
            $body_row->addCell(new Cell($income));
            $body_row->addCell(new Cell($cost));
            $body_row->addCell(new Cell($free));
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
