<?php
namespace App\View\Pages;

use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\Div;
use App\View\Html\HeadCell;
use App\View\Html\Structure;
use App\View\Html\Wrapper;

class PageAdminPaymentAdmin extends PageAdmin
{
    const PAGE_ID = 'payment_admin';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t('payments_admin');
    }

    protected function content(array $query, array $body)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();
        $table->addHeadCell(new HeadCell($this->lang->t('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->t('admin_id')));
        $table->addHeadCell(new HeadCell($this->lang->t('ip')));
        $table->addHeadCell(new HeadCell($this->lang->t('platform'), "platform"));
        $table->addHeadCell(new HeadCell($this->lang->t('date')));

        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS * " .
                "FROM ({$this->settings['transactions_query']}) as t " .
                "WHERE t.payment = 'admin' " .
                "ORDER BY t.timestamp DESC " .
                "LIMIT ?"
        );
        $statement->execute([get_row_limit($this->currentPage->getPageNumber())]);

        $table->setDbRowsCount($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

        foreach ($statement as $row) {
            $bodyRow = new BodyRow();

            if ($query['payid'] == $row['payment_id']) {
                $bodyRow->addClass('highlighted');
            }

            $adminname = $row['aid']
                ? "{$row['adminname']} ({$row['aid']})"
                : $this->lang->t('none');

            $bodyRow->setDbId($row['id']);
            $bodyRow->addCell(new Cell($adminname));
            $bodyRow->addCell(new Cell($row['ip']));

            $cell = new Cell();
            $div = new Div(get_platform($row['platform']));
            $div->addClass('one_line');
            $cell->addContent($div);
            $bodyRow->addCell($cell);

            $bodyRow->addCell(new Cell(convert_date($row['timestamp'])));

            $table->addBodyRow($bodyRow);
        }

        $wrapper->setTable($table);

        return $wrapper->toHtml();
    }
}
