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

        $this->heart->pageTitle = $this->title = $this->lang->t('payments_sms');
    }

    protected function content(array $query, array $body)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();
        $table->addHeadCell(new HeadCell($this->lang->t('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->t('content')));
        $table->addHeadCell(new HeadCell($this->lang->t('number')));
        $table->addHeadCell(new HeadCell($this->lang->t('sms_return_code')));
        $table->addHeadCell(new HeadCell($this->lang->t('income')));
        $table->addHeadCell(new HeadCell($this->lang->t('cost')));
        $table->addHeadCell(new HeadCell($this->lang->t('free_of_charge')));
        $table->addHeadCell(new HeadCell($this->lang->t('ip')));
        $table->addHeadCell(new HeadCell($this->lang->t('platform'), "platform"));
        $table->addHeadCell(new HeadCell($this->lang->t('date')));

        $where = "( t.payment = 'sms' ) ";

        // Wyszukujemy platnosci o konkretnym ID
        if (isset($query['payid'])) {
            if (strlen($where)) {
                $where .= " AND ";
            }

            $where .= $this->db->prepare("( t.payment_id = '%s' ) ", [$query['payid']]);
        }
        // Wyszukujemy dane ktore spelniaja kryteria
        elseif (isset($query['search'])) {
            searchWhere(
                ["t.payment_id", "t.sms_text", "t.sms_code", "t.sms_number"],
                $query['search'],
                $where
            );
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

        $table->setDbRowsAmount($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

        foreach ($result as $row) {
            $bodyRow = new BodyRow();

            if ($query['payid'] == $row['payment_id']) {
                $bodyRow->addClass('highlighted');
            }

            $free = $row['free']
                ? $this->lang->strtoupper($this->lang->t('yes'))
                : $this->lang->strtoupper($this->lang->t('no'));
            $income = $row['income']
                ? number_format($row['income'] / 100.0, 2) . " " . $this->settings->getCurrency()
                : "";
            $cost = $row['cost']
                ? number_format($row['cost'] / 100.0, 2) . " " . $this->settings->getCurrency()
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
