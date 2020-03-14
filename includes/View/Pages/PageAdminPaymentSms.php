<?php
namespace App\View\Pages;

use App\Models\Transaction;
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

class PageAdminPaymentSms extends PageAdmin
{
    const PAGE_ID = 'payment_sms';

    /** @var PriceTextService */
    private $priceTextService;

    /** @var TransactionRepository */
    private $transactionRepository;

    public function __construct(
        PriceTextService $priceTextService,
        TransactionRepository $transactionRepository
    ) {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t('payments_sms');
        $this->priceTextService = $priceTextService;
        $this->transactionRepository = $transactionRepository;
    }

    protected function content(array $query, array $body)
    {
        $payId = array_get($query, 'payid');
        $search = array_get($query, 'search');

        $queryParticle = new QueryParticle();
        $queryParticle->add("( t.payment = 'sms' )");

        if (strlen($payId)) {
            $queryParticle->add("AND ( t.payment_id = ? )", [$payId]);
        } elseif (strlen($search)) {
            $queryParticle->extend(
                create_search_query(
                    ["t.payment_id", "t.sms_text", "t.sms_code", "t.sms_number"],
                    $search
                )
            );
        }

        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS * " .
                "FROM ({$this->transactionRepository->getQuery()}) as t " .
                "WHERE {$queryParticle} " .
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
            ->map(function (Transaction $transaction) use ($payId) {
                $free = $transaction->isFree()
                    ? $this->lang->strtoupper($this->lang->t('yes'))
                    : $this->lang->strtoupper($this->lang->t('no'));

                $income = $this->priceTextService->getPriceText($transaction->getIncome());
                $cost = $this->priceTextService->getPriceText($transaction->getCost());

                $bodyRow = (new BodyRow())
                    ->setDbId($transaction->getPaymentId())
                    ->addCell(new Cell($transaction->getSmsText()))
                    ->addCell(new Cell($transaction->getSmsNumber()))
                    ->addCell(new Cell($transaction->getSmsCode()))
                    ->addCell(new Cell($income))
                    ->addCell(new Cell($cost))
                    ->addCell(new Cell($free))
                    ->addCell(new Cell($transaction->getIp()))
                    ->addCell(new PlatformCell($transaction->getPlatform()))
                    ->addCell(new DateCell($transaction->getTimestamp()));

                if ($payId == $transaction->getPaymentId()) {
                    $bodyRow->addClass('highlighted');
                }

                return $bodyRow;
            })
            ->all();

        $table = (new Structure())
            ->addHeadCell(new HeadCell($this->lang->t('id'), "id"))
            ->addHeadCell(new HeadCell($this->lang->t('content')))
            ->addHeadCell(new HeadCell($this->lang->t('number')))
            ->addHeadCell(new HeadCell($this->lang->t('sms_return_code')))
            ->addHeadCell(new HeadCell($this->lang->t('income')))
            ->addHeadCell(new HeadCell($this->lang->t('cost')))
            ->addHeadCell(new HeadCell($this->lang->t('free_of_charge')))
            ->addHeadCell(new HeadCell($this->lang->t('ip')))
            ->addHeadCell(new HeadCell($this->lang->t('platform'), "platform"))
            ->addHeadCell(new HeadCell($this->lang->t('date')))
            ->addBodyRows($bodyRows)
            ->setDbRowsCount($rowsCount);

        return (new Wrapper())
            ->setTitle($this->title)
            ->setTable($table)
            ->toHtml();
    }
}
