<?php
namespace App\View\Pages;

use App\Models\Transaction;
use App\Repositories\TransactionRepository;
use App\Support\QueryParticle;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\DateCell;
use App\View\Html\HeadCell;
use App\View\Html\PlatformCell;
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
        $queryParticle = new QueryParticle();

        if (isset($query['payid'])) {
            $queryParticle->add(" AND `payment_id` = ? ", [$query['payid']]);
        }

        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS * " .
                "FROM ({$this->transactionRepository->getQuery()}) as t " .
                "WHERE t.payment = 'service_code' $queryParticle" .
                "ORDER BY t.timestamp DESC " .
                "LIMIT ?, ?"
        );
        $statement->execute(
            array_merge(
                $queryParticle->params(),
                get_row_limit($this->currentPage->getPageNumber())
            )
        );
        $rowsCount = $this->db->query('SELECT FOUND_ROWS()')->fetchColumn();

        $bodyRows = collect($statement)
            ->map(function (array $row) {
                return $this->transactionRepository->mapToModel($row);
            })
            ->map(function (Transaction $transaction) use ($query) {
                $bodyRow = new BodyRow();

                if ($query['payid'] == $transaction->getPaymentId()) {
                    $bodyRow->addClass('highlighted');
                }

                $bodyRow->setDbId($transaction->getPaymentId());
                $bodyRow->addCell(new Cell($transaction->getServiceCode()));
                $bodyRow->addCell(new Cell($transaction->getIp()));
                $bodyRow->addCell(new PlatformCell($transaction->getPlatform()));
                $bodyRow->addCell(new DateCell($transaction->getTimestamp()));

                return $bodyRow;
            })
            ->all();

        $table = (new Structure())
            ->addHeadCell(new HeadCell($this->lang->t('id'), "id"))
            ->addHeadCell(new HeadCell($this->lang->t('code')))
            ->addHeadCell(new HeadCell($this->lang->t('ip')))
            ->addHeadCell(new HeadCell($this->lang->t('platform'), "platform"))
            ->addHeadCell(new HeadCell($this->lang->t('date')))
            ->addBodyRows($bodyRows)
            ->enablePagination($this->getPagePath(), $query, $rowsCount);

        return (new Wrapper())
            ->setTitle($this->title)
            ->setTable($table)
            ->toHtml();
    }
}
