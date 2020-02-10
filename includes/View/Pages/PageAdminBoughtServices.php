<?php
namespace App\View\Pages;

use App\Repositories\TransactionRepository;
use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\Support\QueryParticle;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\HeadCell;
use App\View\Html\Link;
use App\View\Html\Structure;
use App\View\Html\UnescapedSimpleText;
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
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);
        $wrapper->setSearch();

        $table = new Structure();
        $table->addHeadCell(new HeadCell($this->lang->t('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->t('payment_admin')));
        $table->addHeadCell(new HeadCell($this->lang->t('payment_id')));
        $table->addHeadCell(new HeadCell($this->lang->t('user')));
        $table->addHeadCell(new HeadCell($this->lang->t('server')));
        $table->addHeadCell(new HeadCell($this->lang->t('service')));
        $table->addHeadCell(new HeadCell($this->lang->t('amount')));
        $table->addHeadCell(
            new HeadCell("{$this->lang->t('nick')}/{$this->lang->t('ip')}/{$this->lang->t('sid')}")
        );
        $table->addHeadCell(new HeadCell($this->lang->t('additional')));
        $table->addHeadCell(new HeadCell($this->lang->t('email')));
        $table->addHeadCell(new HeadCell($this->lang->t('ip')));
        $table->addHeadCell(new HeadCell($this->lang->t('date')));

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

            // Pobranie linku płatności
            $paymentLink = new Link();
            $paymentLink->addClass("dropdown-item");
            $paymentLink->setParam(
                'href',
                $this->url->to(
                    "/admin/payment_{$transaction->getPaymentMethod()}?payid={$transaction->getPaymentId()}"
                )
            );
            $paymentLink->setParam('target', '_blank');
            $paymentLink->addContent($this->lang->t('see_payment'));

            $bodyRow->addAction($paymentLink);

            $bodyRow->setDbId($transaction->getId());
            $bodyRow->addCell(new Cell($transaction->getPaymentMethod()));
            $bodyRow->addCell(new Cell($transaction->getPaymentId()));
            $bodyRow->addCell(new Cell($username));
            $bodyRow->addCell(new Cell($server ? $server->getName() : $this->lang->t('none')));
            $bodyRow->addCell(new Cell($service ? $service->getName() : $this->lang->t('none')));
            $bodyRow->addCell(new Cell($quantity));
            $bodyRow->addCell(new Cell($transaction->getAuthData()));
            $bodyRow->addCell(new Cell(new UnescapedSimpleText($extraData)));
            $bodyRow->addCell(new Cell($transaction->getEmail()));
            $bodyRow->addCell(new Cell($transaction->getIp()));

            $cell = new Cell(convert_date($transaction->getTimestamp()));
            $cell->setParam('headers', 'date');
            $bodyRow->addCell($cell);

            $table->addBodyRow($bodyRow);
        }

        $wrapper->setTable($table);

        return $wrapper->toHtml();
    }
}
