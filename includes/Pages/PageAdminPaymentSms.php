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

        $this->heart->pageTitle = $this->title = $this->lang->translate('payments_sms');
    }

    protected function content(array $query, array $body)
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
        if (isset($query['payid'])) {
            if (strlen($where)) {
                $where .= " AND ";
            }

            $where .= $this->db->prepare("( t.payment_id = '%s' ) ", [$query['payid']]);

            // Podświetlenie konkretnej płatności
            //$row['class'] = "highlighted";
        }
        // Wyszukujemy dane ktore spelniaja kryteria
        else {
            if (isset($query['search'])) {
                searchWhere(
                    ["t.payment_id", "t.sms_text", "t.sms_code", "t.sms_number"],
                    $query['search'],
                    $where
                );
            }
        }

        if (isset($query['payid'])) {
            $where .= $this->db->prepare(" AND `payment_id` = '%d' ", [$query['payid']]);
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
            $bodyRow = new BodyRow();

            if ($query['highlight'] && $query['payid'] == $row['payment_id']) {
                $bodyRow->setParam('class', 'highlighted');
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

            $bodyRow->setDbId($row['payment_id']);
            $bodyRow->addCell(new Cell($row['sms_text']));
            $bodyRow->addCell(new Cell($row['sms_number']));
            $bodyRow->addCell(new Cell($row['sms_code']));
            $bodyRow->addCell(new Cell($income));
            $bodyRow->addCell(new Cell($cost));
            $bodyRow->addCell(new Cell($free));
            $bodyRow->addCell(new Cell(htmlspecialchars($row['ip'])));

            $cell = new Cell();
            $div = new Div(get_platform($row['platform']));
            $div->setParam('class', 'one_line');
            $cell->addContent($div);
            $bodyRow->addCell($cell);

            $bodyRow->addCell(new Cell(convertDate($row['timestamp'])));

            $table->addBodyRow($bodyRow);
        }

        $wrapper->setTable($table);

        return $wrapper->toHtml();
    }
}
