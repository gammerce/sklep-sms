<?php
namespace App\View\Pages\Admin;

use App\Models\Transaction;
use App\Payment\General\PaymentMethod;
use App\Repositories\TransactionRepository;
use App\Services\PriceTextService;
use App\Support\Database;
use App\Support\QueryParticle;
use App\Support\Template;
use App\Translation\TranslationManager;
use App\View\CurrentPage;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\DateTimeCell;
use App\View\Html\HeadCell;
use App\View\Html\InfoTitle;
use App\View\Html\Li;
use App\View\Html\NoneText;
use App\View\Html\PlatformCell;
use App\View\Html\Structure;
use App\View\Html\Ul;
use App\View\Html\UserRef;
use App\View\Html\Wrapper;
use Symfony\Component\HttpFoundation\Request;
use UnexpectedValueException;

class PageAdminPayments extends PageAdmin
{
    const PAGE_ID = "payments";

    /** @var TransactionRepository */
    private $transactionRepository;

    /** @var Database */
    private $db;

    /** @var CurrentPage */
    private $currentPage;

    /** @var PriceTextService */
    private $priceTextService;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        TransactionRepository $transactionRepository,
        PriceTextService $priceTextService,
        Database $db,
        CurrentPage $currentPage
    ) {
        parent::__construct($template, $translationManager);

        $this->transactionRepository = $transactionRepository;
        $this->db = $db;
        $this->currentPage = $currentPage;
        $this->priceTextService = $priceTextService;
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("payments");
    }

    public function getContent(Request $request)
    {
        $recordId = $request->query->get("record");
        $search = $request->query->get("search");
        try {
            $method = new PaymentMethod($request->query->get("method"));
        } catch (UnexpectedValueException $e) {
            $method = null;
        }

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
            ->map(function (Transaction $transaction) use ($recordId) {
                $cost =
                    $this->priceTextService->getPriceText($transaction->getCost()) ?:
                    new NoneText();
                $income =
                    $this->priceTextService->getPriceText($transaction->getIncome()) ?:
                    new NoneText();

                $free = $transaction->isFree()
                    ? $this->lang->strtoupper($this->lang->t("yes"))
                    : $this->lang->strtoupper($this->lang->t("no"));

                return (new BodyRow())
                    ->setDbId($transaction->getPaymentId())
                    ->addCell(new Cell($transaction->getPaymentMethod()))
                    ->addCell(new Cell($income))
                    ->addCell(new Cell($cost))
                    ->addCell(new Cell($free))
                    ->addCell(new Cell($transaction->getPromoCode() ?: new NoneText()))
                    ->addCell(new Cell($transaction->getExternalPaymentId() ?: new NoneText()))
                    ->addCell(new DateTimeCell($transaction->getTimestamp()))
                    ->addCell(new Cell($transaction->getIp()))
                    ->addCell(new PlatformCell($transaction->getPlatform()))
                    ->addCell(new Cell($this->createAdditionalField($transaction)))
                    ->when($recordId == $transaction->getPaymentId(), function (BodyRow $bodyRow) {
                        $bodyRow->addClass("highlighted");
                    });
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
            ->addHeadCell(new HeadCell($this->lang->t("date")))
            ->addHeadCell(new HeadCell($this->lang->t("ip")))
            ->addHeadCell(new HeadCell($this->lang->t("platform"), "platform"))
            ->addHeadCell(new HeadCell($this->lang->t("additional_info")))
            ->addBodyRows($bodyRows)
            ->enablePagination($this->getPagePath(), $request->query->all(), $rowsCount);

        return (new Wrapper())
            ->setTitle($this->getTitle($request))
            ->enableSearch()
            ->setTable($table)
            ->toHtml();
    }

    private function createAdditionalField(Transaction $transaction)
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
                new Li([
                    new InfoTitle($this->lang->t("sms_return_code")),
                    $transaction->getSmsCode(),
                ])
            );
        }

        return $output;
    }
}
