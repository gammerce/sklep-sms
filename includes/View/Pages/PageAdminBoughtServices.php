<?php
namespace App\View\Pages;

use App\Models\Transaction;
use App\Repositories\TransactionRepository;
use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\Support\QueryParticle;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\HeadCell;
use App\View\Html\Link;
use App\View\Html\Structure;
use App\View\Html\RawText;
use App\View\Html\Wrapper;

class PageAdminBoughtServices extends PageAdmin
{
    const PAGE_ID = 'bought_services';

    /** @var TransactionRepository */
    private $transactionRepository;

    public function __construct(TransactionRepository $transactionRepository)
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t('bought_services');
        $this->transactionRepository = $transactionRepository;
    }

    protected function content(array $query, array $body)
    {
        $queryParticle = new QueryParticle();

        if (isset($query['search'])) {
            $queryParticle->extend(
                create_search_query(
                    [
                        "t.id",
                        "t.payment",
                        "t.payment_id",
                        "t.uid",
                        "t.ip",
                        "t.email",
                        "t.auth_data",
                        "CAST(t.timestamp as CHAR)",
                    ],
                    $query['search']
                )
            );
        }

        $where = $queryParticle->isEmpty() ? "" : "WHERE {$queryParticle} ";

        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS * " .
                "FROM ({$this->transactionRepository->getQuery()}) as t " .
                $where .
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
            ->map(function (Transaction $transaction) {
                $service = $this->heart->getService($transaction->getServiceId());
                $server = $this->heart->getServer($transaction->getServerId());

                $username = $transaction->getUserId()
                    ? "{$transaction->getUserName()} ({$transaction->getUserId()})"
                    : $this->lang->t('none');

                $quantity =
                    $transaction->getQuantity() != -1
                        ? $transaction->getQuantity() . ' ' . ($service ? $service->getTag() : '')
                        : $this->lang->t('forever');

                $extraData = [];
                foreach ($transaction->getExtraData() as $key => $value) {
                    if (!strlen($value)) {
                        continue;
                    }

                    if ($key == "password") {
                        $key = $this->lang->t('password');
                    } elseif ($key == "type") {
                        $key = $this->lang->t('type');
                        $value = ExtraFlagType::getTypeName($value);
                    }

                    $extraData[] = htmlspecialchars("$key: $value");
                }
                $extraData = implode('<br />', $extraData);

                $paymentLink = (new Link($this->lang->t('see_payment')))
                    ->addClass("dropdown-item")
                    ->setParam(
                        'href',
                        $this->url->to(
                            "/admin/payment_{$transaction->getPaymentMethod()}?payid={$transaction->getPaymentId()}"
                        )
                    )
                    ->setParam('target', '_blank');

                $cellDate = (new Cell(convert_date($transaction->getTimestamp())))->setParam(
                    'headers',
                    'date'
                );

                return (new BodyRow())
                    ->addAction($paymentLink)
                    ->setDbId($transaction->getId())
                    ->addCell(new Cell($transaction->getPaymentMethod()))
                    ->addCell(new Cell($transaction->getPaymentId()))
                    ->addCell(new Cell($username))
                    ->addCell(new Cell($server ? $server->getName() : $this->lang->t('none')))
                    ->addCell(new Cell($service ? $service->getName() : $this->lang->t('none')))
                    ->addCell(new Cell($quantity))
                    ->addCell(new Cell($transaction->getAuthData()))
                    ->addCell(new Cell(new RawText($extraData)))
                    ->addCell(new Cell($transaction->getEmail()))
                    ->addCell(new Cell($transaction->getIp()))
                    ->addCell($cellDate);
            })
            ->all();

        $table = (new Structure())
            ->addHeadCell(new HeadCell($this->lang->t('id'), "id"))
            ->addHeadCell(new HeadCell($this->lang->t('payment_admin')))
            ->addHeadCell(new HeadCell($this->lang->t('payment_id')))
            ->addHeadCell(new HeadCell($this->lang->t('user')))
            ->addHeadCell(new HeadCell($this->lang->t('server')))
            ->addHeadCell(new HeadCell($this->lang->t('service')))
            ->addHeadCell(new HeadCell($this->lang->t('amount')))
            ->addHeadCell(
                new HeadCell(
                    "{$this->lang->t('nick')}/{$this->lang->t('ip')}/{$this->lang->t('sid')}"
                )
            )
            ->addHeadCell(new HeadCell($this->lang->t('additional')))
            ->addHeadCell(new HeadCell($this->lang->t('email')))
            ->addHeadCell(new HeadCell($this->lang->t('ip')))
            ->addHeadCell(new HeadCell($this->lang->t('date')))
            ->addBodyRows($bodyRows)
            ->enablePagination($this->getPagePath(), $query, $rowsCount);

        return (new Wrapper())
            ->setTitle($this->title)
            ->setSearch()
            ->setTable($table)
            ->toHtml();
    }
}
