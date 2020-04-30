<?php
namespace App\View\Pages\Admin;

use App\Exceptions\UnauthorizedException;
use App\Http\Services\DataFieldService;
use App\Managers\PaymentModuleManager;
use App\Models\PaymentPlatform;
use App\Repositories\PaymentPlatformRepository;
use App\Support\Database;
use App\Support\Template;
use App\Translation\TranslationManager;
use App\View\CurrentPage;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\HeadCell;
use App\View\Html\Input;
use App\View\Html\Option;
use App\View\Html\Structure;
use App\View\Html\Wrapper;
use App\View\Pages\IPageAdminActionBox;
use Symfony\Component\HttpFoundation\Request;

class PageAdminPaymentPlatforms extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = "payment_platforms";

    /** @var PaymentPlatformRepository */
    private $paymentPlatformRepository;

    /** @var DataFieldService */
    private $dataFieldService;

    /** @var Database */
    private $db;

    /** @var CurrentPage */
    private $currentPage;

    /** @var PaymentModuleManager */
    private $paymentModuleManager;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        PaymentPlatformRepository $paymentPlatformRepository,
        DataFieldService $dataFieldService,
        Database $db,
        CurrentPage $currentPage,
        PaymentModuleManager $paymentModuleManager
    ) {
        parent::__construct($template, $translationManager);

        $this->paymentPlatformRepository = $paymentPlatformRepository;
        $this->dataFieldService = $dataFieldService;
        $this->db = $db;
        $this->currentPage = $currentPage;
        $this->paymentModuleManager = $paymentModuleManager;
    }

    public function getPrivilege()
    {
        return "manage_settings";
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("payment_platforms");
    }

    public function getContent(Request $request)
    {
        $addButton = new Input();
        $addButton->setParam("id", "payment_platform_button_add");
        $addButton->setParam("type", "button");
        $addButton->addClass("button");
        $addButton->setParam("value", $this->lang->t("add_payment_platform"));

        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS * FROM `ss_payment_platforms` LIMIT ?, ?"
        );
        $statement->execute(get_row_limit($this->currentPage->getPageNumber()));
        $rowsCount = $this->db->query("SELECT FOUND_ROWS()")->fetchColumn();

        $bodyRows = collect($statement)
            ->map(function (array $row) {
                return $this->paymentPlatformRepository->mapToModel($row);
            })
            ->map(function (PaymentPlatform $paymentPlatform) {
                return (new BodyRow())
                    ->setDbId($paymentPlatform->getId())
                    ->addCell(new Cell($paymentPlatform->getName(), "name"))
                    ->addCell(new Cell($this->lang->t($paymentPlatform->getModuleId())))
                    ->setEditAction(true)
                    ->setDeleteAction(true);
            })
            ->all();

        $table = (new Structure())
            ->addHeadCell(new HeadCell($this->lang->t("id"), "id"))
            ->addHeadCell(new HeadCell($this->lang->t("name")))
            ->addHeadCell(new HeadCell($this->lang->t("module")))
            ->addBodyRows($bodyRows)
            ->enablePagination($this->getPagePath(), $request->query->all(), $rowsCount);

        return (new Wrapper())
            ->setTitle($this->getTitle($request))
            ->setTable($table)
            ->addButton($addButton);
    }

    public function getActionBox($boxId, array $query)
    {
        if (!has_privileges("manage_settings")) {
            throw new UnauthorizedException();
        }

        $output = $this->getActionBoxContent($boxId, $query);

        return [
            "status" => "ok",
            "template" => $output,
        ];
    }

    private function getActionBoxContent($boxId, array $query)
    {
        if ($boxId === "create") {
            $paymentModules = array_map(function ($paymentModuleId) {
                return new Option($this->lang->t($paymentModuleId), $paymentModuleId);
            }, $this->paymentModuleManager->allIds());

            return $this->template->render("admin/action_boxes/payment_platform_add", [
                "paymentModules" => implode("", $paymentModules),
            ]);
        }

        if ($boxId === "edit") {
            $paymentPlatformId = array_get($query, "id");
            $paymentPlatform = $this->paymentPlatformRepository->getOrFail($paymentPlatformId);
            $dataFields = $this->dataFieldService->renderDataFields(
                $paymentPlatform->getModuleId(),
                $paymentPlatform->getData()
            );

            return $this->template->render(
                "admin/action_boxes/payment_platform_edit",
                compact("paymentPlatform", "dataFields")
            );
        }

        return "";
    }
}
