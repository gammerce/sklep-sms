<?php
namespace App\View\Pages;

use App\Repositories\TransactionRepository;
use App\Services\PriceTextService;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\Div;
use App\View\Html\HeadCell;
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
        if (strlen($payId)) {
            if (strlen($where)) {
                $where .= " AND ";
            }

            $where .= $this->db->prepare("( t.payment_id = '%s' ) ", [$payId]);
        }
        // Wyszukujemy dane ktore spelniaja kryteria
        elseif (strlen($search)) {
            searchWhere(
                ["t.payment_id", "t.sms_text", "t.sms_code", "t.sms_number"],
                $search,
                $where
            );
        }

        if (strlen($payId)) {
            $where .= $this->db->prepare(" AND `payment_id` = '%d' ", [$payId]);
        }

        // Jezeli jest jakis where, to dodajemy WHERE
        if (strlen($where)) {
            $where = "WHERE " . $where . " ";
        }

        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS * " .
                "FROM ({$this->transactionRepository->getQuery()}) as t " .
                $where .
                "ORDER BY t.timestamp DESC " .
                "LIMIT ?"
        );
        $statement->execute([get_row_limit($this->currentPage->getPageNumber())]);

        $table->setDbRowsCount($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

        foreach ($statement as $row) {
            $transaction = $this->transactionRepository->mapToModel($row);
            $bodyRow = new BodyRow();

            if ($payId == $transaction->getPaymentId()) {
                $bodyRow->addClass('highlighted');
            }

            $free = $transaction->isFree()
                ? $this->lang->strtoupper($this->lang->t('yes'))
                : $this->lang->strtoupper($this->lang->t('no'));

            $income = $this->priceTextService->getPriceText($transaction->getIncome());
            $cost = $this->priceTextService->getPriceText($transaction->getCost());

            $bodyRow->setDbId($transaction->getPaymentId());
            $bodyRow->addCell(new Cell($transaction->getSmsText()));
            $bodyRow->addCell(new Cell($transaction->getSmsNumber()));
            $bodyRow->addCell(new Cell($transaction->getSmsCode()));
            $bodyRow->addCell(new Cell($income));
            $bodyRow->addCell(new Cell($cost));
            $bodyRow->addCell(new Cell($free));
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

        return $wrapper->toHtml();
    }
}
