<?php

use Admin\Table\BodyRow;
use Admin\Table\Cell;
use Admin\Table\Div;
use Admin\Table\Structure;
use Admin\Table\Wrapper;

$heart->register_page("payment_sms", "PageAdminPaymentSms", "admin");

class PageAdminPaymentSms extends PageAdmin
{
    const PAGE_ID = "payment_sms";

    function __construct()
    {
        global $lang;
        $this->title = $lang->translate('payments_sms');

        parent::__construct();
    }

    protected function content($get, $post)
    {
        global $db, $settings, $lang, $G_PAGE;

        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();

        $cell = new Cell($lang->translate('id'));
        $cell->setParam('headers', 'id');
        $table->addHeadCell($cell);

        $table->addHeadCell(new Cell($lang->translate('content')));
        $table->addHeadCell(new Cell($lang->translate('number')));
        $table->addHeadCell(new Cell($lang->translate('sms_return_code')));
        $table->addHeadCell(new Cell($lang->translate('income')));
        $table->addHeadCell(new Cell($lang->translate('cost')));
        $table->addHeadCell(new Cell($lang->translate('free_of_charge')));
        $table->addHeadCell(new Cell($lang->translate('ip')));

        $cell = new Cell($lang->translate('platform'));
        $cell->setParam('headers', 'platform');
        $table->addHeadCell($cell);

        $table->addHeadCell(new Cell($lang->translate('date')));

        $where = "( t.payment = 'sms' ) ";

        // Wyszukujemy platnosci o konkretnym ID
        if (isset($get['payid'])) {
            if (strlen($where)) {
                $where .= " AND ";
            }

            $where .= $db->prepare("( t.payment_id = '%s' ) ", [$get['payid']]);

            // Podświetlenie konkretnej płatności
            //$row['class'] = "highlighted";
        } // Wyszukujemy dane ktore spelniaja kryteria
        else {
            if (isset($get['search'])) {
                searchWhere(["t.payment_id", "t.sms_text", "t.sms_code", "t.sms_number"], $get['search'], $where);
            }
        }

        if (isset($get['payid'])) {
            $where .= $db->prepare(" AND `payment_id` = '%d' ", [$get['payid']]);
        }

        // Jezeli jest jakis where, to dodajemy WHERE
        if (strlen($where)) {
            $where = "WHERE " . $where . " ";
        }

        $result = $db->query(
            "SELECT SQL_CALC_FOUND_ROWS * " .
            "FROM ({$settings['transactions_query']}) as t " .
            $where .
            "ORDER BY t.timestamp DESC " .
            "LIMIT " . get_row_limit($G_PAGE)
        );

        $table->setDbRowsAmount($db->get_column('SELECT FOUND_ROWS()', 'FOUND_ROWS()'));

        while ($row = $db->fetch_array_assoc($result)) {
            $body_row = new BodyRow();

            if ($get['highlight'] && $get['payid'] == $row['payment_id']) {
                $body_row->setParam('class', 'highlighted');
            }

            $free = $row['free'] ? $lang->strtoupper($lang->translate('yes')) : $lang->strtoupper($lang->translate('no'));
            $income = $row['income'] ? number_format($row['income'] / 100.0, 2) . " " . $settings['currency'] : "";
            $cost = $row['cost'] ? number_format($row['cost'] / 100.0, 2) . " " . $settings['currency'] : "";

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