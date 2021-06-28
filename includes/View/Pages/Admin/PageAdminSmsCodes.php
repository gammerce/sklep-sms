<?php
namespace App\View\Pages\Admin;

use App\Exceptions\EntityNotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Models\SmsCode;
use App\Repositories\SmsCodeRepository;
use App\Repositories\SmsPriceRepository;
use App\Support\Database;
use App\Support\Money;
use App\Support\PriceTextService;
use App\Theme\Template;
use App\Translation\TranslationManager;
use App\User\Permission;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\ExpirationDateCell;
use App\View\Html\HeadCell;
use App\View\Html\Input;
use App\View\Html\Option;
use App\View\Html\Structure;
use App\View\Html\Wrapper;
use App\View\Pages\IPageAdminActionBox;
use App\View\Pagination\PaginationFactory;
use Symfony\Component\HttpFoundation\Request;

class PageAdminSmsCodes extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = "sms_codes";

    private SmsPriceRepository $smsPriceRepository;
    private PriceTextService $priceTextService;
    private SmsCodeRepository $smsCodeRepository;
    private Database $db;
    private PaginationFactory $paginationFactory;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        SmsPriceRepository $smsPriceRepository,
        SmsCodeRepository $smsCodeRepository,
        PriceTextService $priceTextService,
        Database $db,
        PaginationFactory $paginationFactory
    ) {
        parent::__construct($template, $translationManager);

        $this->smsPriceRepository = $smsPriceRepository;
        $this->priceTextService = $priceTextService;
        $this->smsCodeRepository = $smsCodeRepository;
        $this->db = $db;
        $this->paginationFactory = $paginationFactory;
    }

    public function getPrivilege(): Permission
    {
        return Permission::VIEW_SMS_CODES();
    }

    public function getTitle(Request $request = null): string
    {
        return $this->lang->t("sms_codes");
    }

    public function getContent(Request $request)
    {
        $pagination = $this->paginationFactory->create($request);

        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS * " .
                "FROM `ss_sms_codes` " .
                "WHERE `free` = '1' " .
                "LIMIT ?, ?"
        );
        $statement->execute($pagination->getSqlLimit());
        $rowsCount = $this->db->query("SELECT FOUND_ROWS()")->fetchColumn();

        $bodyRows = collect($statement)
            ->map(fn(array $row) => $this->smsCodeRepository->mapToModel($row))
            ->map(
                fn(SmsCode $smsCode) => (new BodyRow())
                    ->setDbId($smsCode->getId())
                    ->addCell(new Cell($smsCode->getCode()))
                    ->addCell(
                        new Cell(
                            $this->priceTextService->getPriceGrossText($smsCode->getSmsPrice())
                        )
                    )
                    ->addCell(new ExpirationDateCell($smsCode->getExpiresAt()))
                    ->setDeleteAction(can(Permission::MANAGE_SMS_CODES()))
            )
            ->all();

        $table = (new Structure())
            ->addHeadCell(new HeadCell($this->lang->t("id"), "id"))
            ->addHeadCell(new HeadCell($this->lang->t("sms_code")))
            ->addHeadCell(new HeadCell($this->lang->t("sms_price")))
            ->addHeadCell(new HeadCell($this->lang->t("expires")))
            ->addBodyRows($bodyRows)
            ->enablePagination($this->getPagePath(), $pagination, $rowsCount);

        $wrapper = (new Wrapper())->setTitle($this->getTitle($request))->setTable($table);

        if (can(Permission::MANAGE_SMS_CODES())) {
            $button = (new Input())
                ->setParam("id", "sms_code_button_add")
                ->setParam("type", "button")
                ->addClass("button")
                ->setParam("value", $this->lang->t("add_code"));

            $wrapper->addButton($button);
        }

        return $wrapper->toHtml();
    }

    public function getActionBox($boxId, array $query): string
    {
        if (cannot(Permission::MANAGE_SMS_CODES())) {
            throw new UnauthorizedException();
        }

        switch ($boxId) {
            case "add":
                $smsPrices = collect($this->smsPriceRepository->all())
                    ->map(
                        fn(Money $smsPrice) => new Option(
                            $this->priceTextService->getPriceGrossText($smsPrice),
                            $smsPrice->asInt()
                        )
                    )
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
