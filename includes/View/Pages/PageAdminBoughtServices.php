<?php
namespace App\View\Pages;

use App\Models\Transaction;
use App\Repositories\TransactionRepository;
use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\Support\QueryParticle;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\HeadCell;
use App\View\Html\PaymentRef;
use App\View\Html\RawText;
use App\View\Html\ServerRef;
use App\View\Html\ServiceRef;
use App\View\Html\Structure;
use App\View\Html\UserRef;
use App\View\Html\Wrapper;

class PageAdminBoughtServices extends PageAdmin
{
    const PAGE_ID = "bought_services";

    /** @var TransactionRepository */
    private $transactionRepository;

    public function __construct(TransactionRepository $transactionRepository)
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t("bought_services");
        $this->transactionRepository = $transactionRepository;
    }

    public function content(array $query, array $body)
    {
        $search = array_get($query, "search");

        $queryParticle = new QueryParticle();

        if (strlen($search)) {
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
                    $search
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
        $rowsCount = $this->db->query("SELECT FOUND_ROWS()")->fetchColumn();

        $bodyRows = collect($statement)
            ->map(function (array $row) {
                return $this->transactionRepository->mapToModel($row);
            })
            ->map(function (Transaction $transaction) {
                $service = $this->heart->getService($transaction->getServiceId());
                $server = $this->heart->getServer($transaction->getServerId());

                $userEntry = $transaction->getUserId()
                    ? new UserRef($transaction->getUserId(), $transaction->getUserName())
                    : $this->lang->t("none");

                $serverEntry = $server
                    ? new ServerRef($server->getId(), $server->getName())
                    : $this->lang->t("none");
                $serviceEntry = $service
                    ? new ServiceRef($service->getId(), $service->getName())
                    : $this->lang->t("none");

                $quantity =
                    $transaction->getQuantity() != -1
                        ? $transaction->getQuantity() . " " . ($service ? $service->getTag() : "")
                        : $this->lang->t("forever");

                $extraData = collect($transaction->getExtraData())
                    ->filter(function ($value) {
                        return strlen($value);
                    })
                    ->mapWithKeys(function ($value, $key) {
                        if ($key == "password") {
                            $key = $this->lang->t("password");
                        } elseif ($key == "type") {
                            $key = $this->lang->t("type");
                            $value = ExtraFlagType::getTypeName($value);
                        }

                        return htmlspecialchars("$key: $value");
                    })
                    ->join("<br />");

                return (new BodyRow())
                    ->setDbId($transaction->getId())
                    ->addCell(
                        new Cell(
                            new PaymentRef(
                                $transaction->getPaymentId(),
                                $transaction->getPaymentMethod()
                            )
                        )
                    )
                    ->addCell(new Cell($userEntry))
                    ->addCell(new Cell($serverEntry))
                    ->addCell(new Cell($serviceEntry))
                    ->addCell(new Cell($quantity))
                    ->addCell(new Cell($transaction->getAuthData()))
                    ->addCell(new Cell(new RawText($extraData)))
                    ->addCell(new Cell($transaction->getEmail()))
                    ->addCell(new Cell($transaction->getIp()))
                    ->addCell(new Cell(convert_date($transaction->getTimestamp()), "date"));
            })
            ->all();

        $table = (new Structure())
            ->addHeadCell(new HeadCell($this->lang->t("id"), "id"))
            ->addHeadCell(new HeadCell($this->lang->t("payment_id")))
            ->addHeadCell(new HeadCell($this->lang->t("user")))
            ->addHeadCell(new HeadCell($this->lang->t("server")))
            ->addHeadCell(new HeadCell($this->lang->t("service")))
            ->addHeadCell(new HeadCell($this->lang->t("amount")))
            ->addHeadCell(
                new HeadCell(
                    "{$this->lang->t("nick")}/{$this->lang->t("ip")}/{$this->lang->t("sid")}"
                )
            )
            ->addHeadCell(new HeadCell($this->lang->t("additional")))
            ->addHeadCell(new HeadCell($this->lang->t("email")))
            ->addHeadCell(new HeadCell($this->lang->t("ip")))
            ->addHeadCell(new HeadCell($this->lang->t("date")))
            ->addBodyRows($bodyRows)
            ->enablePagination($this->getPagePath(), $query, $rowsCount);

        return (new Wrapper())
            ->setTitle($this->title)
            ->enableSearch()
            ->setTable($table)
            ->toHtml();
    }
}
