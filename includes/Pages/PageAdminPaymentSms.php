<?php
namespace App\Pages;

use App\Html\BodyRow;
use App\Html\Cell;
use App\Html\Div;
use App\Html\HeadCell;
use App\Html\Structure;
use App\Html\Wrapper;

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
        $table->addHeadCell(new HeadCell($this->lang->translate('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->translate('content')));
        $table->addHeadCell(new HeadCell($this->lang->translate('number')));
        $table->addHeadCell(new HeadCell($this->lang->translate('sms_return_code')));
        $table->addHeadCell(new HeadCell($this->lang->translate('income')));
        $table->addHeadCell(new HeadCell($this->lang->translate('cost')));
        $table->addHeadCell(new HeadCell($this->lang->translate('free_of_charge')));
        $table->addHeadCell(new HeadCell($this->lang->translate('ip')));
        $table->addHeadCell(new HeadCell($this->lang->translate('platform'), "platform"));
        $table->addHeadCell(new HeadCell($this->lang->translate('date')));

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
                $bodyRow->addClass('highlighted');
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
            $bodyRow->addCell(new Cell($row['ip']));

            $cell = new Cell();
            $div = new Div(get_platform($row['platform']));
            $div->addClass('one_line');
            $cell->addContent($div);
            $bodyRow->addCell($cell);

            $bodyRow->addCell(new Cell(convertDate($row['timestamp'])));

            $table->addBodyRow($bodyRow);
        }

        $wrapper->setTable($table);

        return $wrapper->toHtml();
    }
}
