<?php
namespace App\View\Pages\Admin;

use App\Models\Transaction;
use App\Payment\Invoice\InvoiceService;
use App\Repositories\TransactionRepository;
use App\Support\Database;
use App\Support\PriceTextService;
use App\Support\QueryParticle;
use App\Theme\Template;
use App\Translation\TranslationManager;
use App\User\Permission;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\DateTimeCell;
use App\View\Html\DOMElement;
use App\View\Html\HeadCell;
use App\View\Html\InfoTitle;
use App\View\Html\InvoiceRef;
use App\View\Html\Li;
use App\View\Html\NoneText;
use App\View\Html\PlainTextCell;
use App\View\Html\PlatformCell;
use App\View\Html\Structure;
use App\View\Html\Ul;
use App\View\Html\UserRef;
use App\View\Html\Wrapper;
use App\View\Pagination\PaginationFactory;
use Symfony\Component\HttpFoundation\Request;

class PageAdminPayments extends PageAdmin
{
    const PAGE_ID = "payments";

    private TransactionRepository $transactionRepository;
    private Database $db;
    private PriceTextService $priceTextService;
    private PaginationFactory $paginationFactory;
    private InvoiceService $invoiceService;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        TransactionRepository $transactionRepository,
        PriceTextService $priceTextService,
        Database $db,
        PaginationFactory $paginationFactory,
        InvoiceService $invoiceService
    ) {
        parent::__construct($template, $translationManager);

        $this->transactionRepository = $transactionRepository;
        $this->db = $db;
        $this->priceTextService = $priceTextService;
        $this->paginationFactory = $paginationFactory;
        $this->invoiceService = $invoiceService;
    }

    public function getPrivilege(): Permission
    {
        return Permission::INCOME_VIEW();
    }

    public function getTitle(Request $request = null): string
    {
        return $this->lang->t("payments");
    }

    public function getContent(Request $request)
    {
        $recordId = $request->query->get("record", "");
        $search = $request->query->get("search");
        $method = as_payment_method($request->query->get("method"));

        $pagination = $this->paginationFactory->create($request);
        $queryParticle = new QueryParticle();

        if (strlen($recordId)) {
            $queryParticle->add("`payment_id` = ?", [$recordId]);
        }

        if ($method) {
            $queryParticle->add("t.payment = ?", [$method]);
        }

        if (strlen($search)) {
            $queryParticle->extend(
                create_search_query(
                    [
                        "t.payment_id",
                        "t.invoice_id",
                        "t.external_payment_id",
                        "t.cost",
                        "t.income",
                        "t.ip",
                        "t.sms_text",
                        "t.sms_code",
                        "t.sms_number",
                    ],
                    $search
                )
            );
        }

        if ($queryParticle->isEmpty()) {
            $whereQuery = "";
        } else {
            $whereQuery = "WHERE " . $queryParticle->text(" AND ") . " ";
        }

        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS * " .
                "FROM ({$this->transactionRepository->getQuery()}) as t " .
                $whereQuery .
                "ORDER BY t.timestamp DESC " .
                "LIMIT ?, ?"
        );
        $statement->bindAndExecute(
            array_merge($queryParticle->params(), $pagination->getSqlLimit())
        );
        $rowsCount = $this->db->query("SELECT FOUND_ROWS()")->fetchColumn();

        $bodyRows = collect($statement)
            ->map(fn(array $row) => $this->transactionRepository->mapToModel($row))
            ->map(function (Transaction $transaction) use ($recordId) {
                $cost =
                    $this->priceTextService->getPriceText($transaction->getCost()) ?:
                    new NoneText();
                $income =
                    $this->priceTextService->getPriceText($transaction->getIncome()) ?:
                    new NoneText();

                $free = $transaction->isFree()
                    ? to_upper($this->lang->t("yes"))
                    : to_upper($this->lang->t("no"));

                $invoiceEntry = $transaction->getInvoiceId()
                    ? new InvoiceRef($transaction->getInvoiceId())
                    : new NoneText();

                return (new BodyRow())
                    ->setDbId($transaction->getPaymentId())
                    ->addCell(
                        new PlainTextCell($this->lang->t((string) $transaction->getPaymentMethod()))
                    )
                    ->addCell(new PlainTextCell($income))
                    ->addCell(new PlainTextCell($cost))
                    ->addCell(new PlainTextCell($free))
                    ->addCell(new Cell($transaction->getPromoCode() ?: new NoneText()))
                    ->addCell(new Cell($transaction->getExternalPaymentId() ?: new NoneText()))
                    ->when(
                        $this->invoiceService->isConfigured(),
                        fn(BodyRow $bodyRow) => $bodyRow->addCell(new Cell($invoiceEntry))
                    )
                    ->addCell(new DateTimeCell($transaction->getTimestamp()))
                    ->addCell(new PlainTextCell($transaction->getIp(), "ip"))
                    ->addCell(new PlatformCell($transaction->getPlatform()))
                    ->addCell(new Cell($this->createAdditionalField($transaction)))
                    ->when(
                        $recordId == $transaction->getPaymentId(),
                        fn(BodyRow $bodyRow) => $bodyRow->addClass("highlighted")
                    );
            })
            ->all();

        $table = (new Structure())
            ->addHeadCell(new HeadCell($this->lang->t("id"), "id"))
            ->addHeadCell(new HeadCell($this->lang->t("method")))
            ->addHeadCell(new HeadCell($this->lang->t("income")))
            ->addHeadCell(new HeadCell($this->lang->t("cost")))
            ->addHeadCell(new HeadCell($this->lang->t("free_of_charge")))
            ->addHeadCell(new HeadCell($this->lang->t("promo_code")))
            ->addHeadCell(new HeadCell($this->lang->t("external_id")))
            ->when(
                $this->invoiceService->isConfigured(),
                fn(Structure $structure) => $structure->addHeadCell(
                    new HeadCell($this->lang->t("invoice"))
                )
            )
            ->addHeadCell(new HeadCell($this->lang->t("date")))
            ->addHeadCell(new HeadCell($this->lang->t("ip"), "ip"))
            ->addHeadCell(new HeadCell($this->lang->t("platform"), "platform"))
            ->addHeadCell(new HeadCell($this->lang->t("additional")))
            ->addBodyRows($bodyRows)
            ->enablePagination($this->getPagePath(), $pagination, $rowsCount);

        return (new Wrapper())
            ->setTitle($this->getTitle($request))
            ->enableSearch()
            ->setTable($table)
            ->toHtml();
    }

    private function createAdditionalField(Transaction $transaction): DOMElement
    {
        $output = new Ul();

        if ($transaction->getAdminId()) {
            $output->addContent(
                new Li([
                    new InfoTitle($this->lang->t("admin_id")),
                    new UserRef($transaction->getAdminId(), $transaction->getAdminName()),
                ])
            );
        }

        if ($transaction->getSmsText()) {
            $output->addContent(
                new Li([new InfoTitle($this->lang->t("content")), $transaction->getSmsText()])
            );
        }

        if ($transaction->getSmsNumber()) {
            $output->addContent(
                new Li([new InfoTitle($this->lang->t("number")), $transaction->getSmsNumber()])
            );
        }

        if ($transaction->getSmsCode()) {
            $output->addContent(
                new Li([new InfoTitle($this->lang->t("code")), $transaction->getSmsCode()])
            );
        }

        return $output;
    }
}
