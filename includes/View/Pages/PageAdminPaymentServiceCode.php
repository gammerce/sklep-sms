<?php
namespace App\View\Pages;

use App\Repositories\TransactionRepository;
use App\Support\QueryParticle;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\Div;
use App\View\Html\HeadCell;
use App\View\Html\Structure;
use App\View\Html\Wrapper;

class PageAdminPaymentServiceCode extends PageAdmin
{
    const PAGE_ID = 'payment_service_code';

    /** @var TransactionRepository */
    private $transactionRepository;

    public function __construct(TransactionRepository $transactionRepository)
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t('payments_service_code');
        $this->transactionRepository = $transactionRepository;
    }

    protected function content(array $query, array $body)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();
        $table->addHeadCell(new HeadCell($this->lang->t('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->t('code')));
        $table->addHeadCell(new HeadCell($this->lang->t('ip')));
        $table->addHeadCell(new HeadCell($this->lang->t('platform'), "platform"));
        $table->addHeadCell(new HeadCell($this->lang->t('date')));

        $queryParticle = new QueryParticle();

        if (isset($query['payid'])) {
            $queryParticle->add(" AND `payment_id` = ? ", [$query['payid']]);
        }

        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS * " .
                "FROM ({$this->transactionRepository->getQuery()}) as t " .
                "WHERE t.payment = 'service_code' $queryParticle" .
                "ORDER BY t.timestamp DESC " .
                "LIMIT ?"
        );
        $statement->execute(
            array_merge($queryParticle->params(), [
                get_row_limit($this->currentPage->getPageNumber()),
            ])
        );

        $table->setDbRowsCount($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

        foreach ($statement as $row) {
            $transaction = $this->transactionRepository->mapToModel($row);
            $bodyRow = new BodyRow();

            if ($query['payid'] == $transaction->getPaymentId()) {
                $bodyRow->addClass('highlighted');
            }

            $bodyRow->setDbId($transaction->getPaymentId());
            $bodyRow->addCell(new Cell($transaction->getServiceCode()));
            $bodyRow->addCell(new Cell($transaction->getIp()));

            $cell = new Cell();
            $div = new Div(get_platform($transaction->getPlatform()));
            $div->addClass('one_line');
            $cell->addContent($div);
            $bodyRow->addCell($cell);

            $bodyRow->addCell(new Cell(convert_date($transaction->getTimestamp())));

            $table->addBodyRow($bodyRow);
        }

        $wrapper->setTable($table);

        return $wrapper;
    }
}
