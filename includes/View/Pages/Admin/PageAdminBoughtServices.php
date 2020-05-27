<?php
namespace App\View\Pages\Admin;

use App\Managers\ServerManager;
use App\Managers\ServiceManager;
use App\Models\Transaction;
use App\Repositories\TransactionRepository;
use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\Support\Database;
use App\Support\QueryParticle;
use App\Support\Template;
use App\Translation\TranslationManager;
use App\View\CurrentPage;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\DateTimeCell;
use App\View\Html\HeadCell;
use App\View\Html\NoneText;
use App\View\Html\PaymentRef;
use App\View\Html\PlainTextCell;
use App\View\Html\RawHtml;
use App\View\Html\ServerRef;
use App\View\Html\ServiceRef;
use App\View\Html\Structure;
use App\View\Html\UserRef;
use App\View\Html\Wrapper;
use Symfony\Component\HttpFoundation\Request;

class PageAdminBoughtServices extends PageAdmin
{
    const PAGE_ID = "bought_services";

    /** @var TransactionRepository */
    private $transactionRepository;

    /** @var Database */
    private $db;

    /** @var CurrentPage */
    private $currentPage;

    /** @var ServiceManager */
    private $serviceManager;

    /** @var ServerManager */
    private $serverManager;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        TransactionRepository $transactionRepository,
        Database $db,
        CurrentPage $currentPage,
        ServiceManager $serviceManager,
        ServerManager $serverManager
    ) {
        parent::__construct($template, $translationManager);

        $this->transactionRepository = $transactionRepository;
        $this->db = $db;
        $this->currentPage = $currentPage;
        $this->serviceManager = $serviceManager;
        $this->serverManager = $serverManager;
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("bought_services");
    }

    public function getContent(Request $request)
    {
        $search = $request->query->get("search");

        $queryParticle = new QueryParticle();

        if (strlen($search)) {
            $queryParticle->extend(
                create_search_query(
                    [
                        "t.id",
                        "t.payment",
                        "t.payment_id",
                        "t.user_id",
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
                $service = $this->serviceManager->getService($transaction->getServiceId());
                $server = $this->serverManager->getServer($transaction->getServerId());

                $userEntry = $transaction->getUserId()
                    ? new UserRef($transaction->getUserId(), $transaction->getUserName())
                    : new NoneText();

                $serverEntry = $server
                    ? new ServerRef($server->getId(), $server->getName())
                    : new NoneText();
                $serviceEntry = $service
                    ? new ServiceRef($service->getId(), $service->getName())
                    : new NoneText();

                $quantity = $transaction->isForever()
                    ? $this->lang->t("forever")
                    : $transaction->getQuantity() . " " . ($service ? $service->getTag() : "");

                $paymentEntry = $transaction->getPaymentMethod()
                    ? new PaymentRef($transaction->getPaymentId(), $transaction->getPaymentMethod())
                    : new NoneText();

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
                    ->addCell(new Cell($paymentEntry))
                    ->addCell(new Cell($userEntry))
                    ->addCell(new Cell($serverEntry))
                    ->addCell(new Cell($serviceEntry))
                    ->addCell(new PlainTextCell($quantity))
                    ->addCell(new PlainTextCell($transaction->getAuthData()))
                    ->addCell(new Cell($transaction->getPromoCode() ?: new NoneText()))
                    ->addCell(new Cell(new RawHtml($extraData)))
                    ->addCell(new PlainTextCell($transaction->getEmail()))
                    ->addCell(new PlainTextCell($transaction->getIp(), "ip"))
                    ->addCell(new DateTimeCell($transaction->getTimestamp()));
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
            ->addHeadCell(new HeadCell($this->lang->t("promo_code")))
            ->addHeadCell(new HeadCell($this->lang->t("additional")))
            ->addHeadCell(new HeadCell($this->lang->t("email")))
            ->addHeadCell(new HeadCell($this->lang->t("ip")))
            ->addHeadCell(new HeadCell($this->lang->t("date")))
            ->addBodyRows($bodyRows)
            ->enablePagination($this->getPagePath(), $request->query->all(), $rowsCount);

        return (new Wrapper())
            ->setTitle($this->getTitle($request))
            ->enableSearch()
            ->setTable($table)
            ->toHtml();
    }
}
