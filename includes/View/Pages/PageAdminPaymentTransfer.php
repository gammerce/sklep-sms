<?php
namespace App\View\Pages;

use App\Repositories\TransactionRepository;
use App\Services\PriceTextService;
use App\Support\QueryParticle;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\DateCell;
use App\View\Html\HeadCell;
use App\View\Html\PlatformCell;
use App\View\Html\Structure;
use App\View\Html\Wrapper;

class PageAdminPaymentTransfer extends PageAdmin
{
    const PAGE_ID = 'payment_transfer';

    /** @var PriceTextService */
    private $priceTextService;

    /** @var TransactionRepository */
    private $transactionRepository;

    public function __construct(
        PriceTextService $priceTextService,
        TransactionRepository $transactionRepository
    ) {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t('payments_transfer');
        $this->priceTextService = $priceTextService;
        $this->transactionRepository = $transactionRepository;
    }

    protected function content(array $query, array $body)
    {
        $table = new Structure();

        $table->addHeadCell(new HeadCell($this->lang->t('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->t('cost')));
        $table->addHeadCell(new HeadCell($this->lang->t('ip')));
        $table->addHeadCell(new HeadCell($this->lang->t('platform'), "platform"));
        $table->addHeadCell(new HeadCell($this->lang->t('date')));

        $queryParticle = new QueryParticle();
        $queryParticle->add("( t.payment = 'transfer' )");

        if (isset($query['search'])) {
            $queryParticle->extend(
                create_search_query(["t.payment_id", "t.income", "t.ip"], $query['search'])
            );
        }

        if (isset($query['payid'])) {
            $queryParticle->add("AND `payment_id` = ?", [$query['payid']]);
        }

        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS * " .
                "FROM ({$this->transactionRepository->getQuery()}) as t " .
                "WHERE $queryParticle " .
                "ORDER BY t.timestamp DESC " .
                "LIMIT ?, ?"
        );
        $statement->execute(
            array_merge(
                $queryParticle->params(),
                get_row_limit($this->currentPage->getPageNumber())
            )
        );

        $table->setDbRowsCount($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

        foreach ($statement as $row) {
            $transaction = $this->transactionRepository->mapToModel($row);
            $bodyRow = new BodyRow();

            if ($query['payid'] == $transaction->getPaymentId()) {
                $bodyRow->addClass('highlighted');
            }

            $income = $this->priceTextService->getPriceText($transaction->getIncome());

            $bodyRow->setDbId($transaction->getPaymentId());
            $bodyRow->addCell(new Cell($income));
            $bodyRow->addCell(new Cell($transaction->getIp()));
            $bodyRow->addCell(new PlatformCell($transaction->getPlatform()));
            $bodyRow->addCell(new DateCell($transaction->getTimestamp()));

            $table->addBodyRow($bodyRow);
        }

        return (new Wrapper())
            ->setTitle($this->title)
            ->setTable($table)
            ->toHtml();
    }
}
