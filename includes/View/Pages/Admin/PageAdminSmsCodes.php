<?php
namespace App\View\Pages\Admin;

use App\Exceptions\EntityNotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Models\SmsCode;
use App\Repositories\SmsCodeRepository;
use App\Repositories\SmsPriceRepository;
use App\Services\PriceTextService;
use App\Support\Database;
use App\Support\Template;
use App\Translation\TranslationManager;
use App\View\CurrentPage;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\ExpirationDateCell;
use App\View\Html\HeadCell;
use App\View\Html\Input;
use App\View\Html\Option;
use App\View\Html\Structure;
use App\View\Html\Wrapper;
use App\View\Pages\IPageAdminActionBox;
use Symfony\Component\HttpFoundation\Request;

class PageAdminSmsCodes extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = "sms_codes";

    /** @var SmsPriceRepository */
    private $smsPriceRepository;

    /** @var PriceTextService */
    private $priceTextService;

    /** @var SmsCodeRepository */
    private $smsCodeRepository;

    /** @var Database */
    private $db;

    /** @var CurrentPage */
    private $currentPage;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        SmsPriceRepository $smsPriceRepository,
        SmsCodeRepository $smsCodeRepository,
        PriceTextService $priceTextService,
        Database $db,
        CurrentPage $currentPage
    ) {
        parent::__construct($template, $translationManager);

        $this->smsPriceRepository = $smsPriceRepository;
        $this->priceTextService = $priceTextService;
        $this->smsCodeRepository = $smsCodeRepository;
        $this->db = $db;
        $this->currentPage = $currentPage;
    }

    public function getPrivilege()
    {
        return "view_sms_codes";
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("sms_codes");
    }

    public function getContent(Request $request)
    {
        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS * " .
                "FROM `ss_sms_codes` " .
                "WHERE `free` = '1' " .
                "LIMIT ?, ?"
        );
        $statement->execute(get_row_limit($this->currentPage->getPageNumber()));
        $rowsCount = $this->db->query("SELECT FOUND_ROWS()")->fetchColumn();

        $bodyRows = collect($statement)
            ->map(function (array $row) {
                return $this->smsCodeRepository->mapToModel($row);
            })
            ->map(function (SmsCode $smsCode) {
                return (new BodyRow())
                    ->setDbId($smsCode->getId())
                    ->addCell(new Cell($smsCode->getCode()))
                    ->addCell(
                        new Cell(
                            $this->priceTextService->getPriceGrossText($smsCode->getSmsPrice())
                        )
                    )
                    ->addCell(new ExpirationDateCell($smsCode->getExpiresAt()))
                    ->setDeleteAction(has_privileges("manage_sms_codes"));
            })
            ->all();

        $table = (new Structure())
            ->addHeadCell(new HeadCell($this->lang->t("id"), "id"))
            ->addHeadCell(new HeadCell($this->lang->t("sms_code")))
            ->addHeadCell(new HeadCell($this->lang->t("sms_price")))
            ->addHeadCell(new HeadCell($this->lang->t("expires")))
            ->addBodyRows($bodyRows)
            ->enablePagination($this->getPagePath(), $request->query->all(), $rowsCount);

        $wrapper = (new Wrapper())->setTitle($this->getTitle($request))->setTable($table);

        if (has_privileges("manage_sms_codes")) {
            $button = (new Input())
                ->setParam("id", "sms_code_button_add")
                ->setParam("type", "button")
                ->addClass("button")
                ->setParam("value", $this->lang->t("add_code"));

            $wrapper->addButton($button);
        }

        return $wrapper->toHtml();
    }

    public function getActionBox($boxId, array $query)
    {
        if (!has_privileges("manage_sms_codes")) {
            throw new UnauthorizedException();
        }

        switch ($boxId) {
            case "add":
                $smsPrices = collect($this->smsPriceRepository->all())
                    ->map(function ($smsPrice) {
                        return new Option(
                            $this->priceTextService->getPriceGrossText($smsPrice),
                            $smsPrice
                        );
                    })
                    ->join();

                return $this->template->render(
                    "admin/action_boxes/sms_code_add",
                    compact("smsPrices")
                );

            default:
                throw new EntityNotFoundException();
        }
    }
}
