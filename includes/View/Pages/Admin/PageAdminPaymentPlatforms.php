<?php
namespace App\View\Pages\Admin;

use App\Exceptions\EntityNotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Http\Services\DataFieldService;
use App\Managers\PaymentModuleManager;
use App\Models\PaymentPlatform;
use App\Repositories\PaymentPlatformRepository;
use App\Support\Database;
use App\Support\Template;
use App\Translation\TranslationManager;
use App\User\Permission;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\HeadCell;
use App\View\Html\Input;
use App\View\Html\Option;
use App\View\Html\Structure;
use App\View\Html\Wrapper;
use App\View\Pages\IPageAdminActionBox;
use App\View\Pagination\PaginationFactory;
use Symfony\Component\HttpFoundation\Request;

class PageAdminPaymentPlatforms extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = "payment_platforms";

    private PaymentPlatformRepository $paymentPlatformRepository;
    private DataFieldService $dataFieldService;
    private Database $db;
    private PaymentModuleManager $paymentModuleManager;
    private PaginationFactory $paginationFactory;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        PaymentPlatformRepository $paymentPlatformRepository,
        DataFieldService $dataFieldService,
        Database $db,
        PaymentModuleManager $paymentModuleManager,
        PaginationFactory $paginationFactory
    ) {
        parent::__construct($template, $translationManager);

        $this->paymentPlatformRepository = $paymentPlatformRepository;
        $this->dataFieldService = $dataFieldService;
        $this->db = $db;
        $this->paymentModuleManager = $paymentModuleManager;
        $this->paginationFactory = $paginationFactory;
    }

    public function getPrivilege(): Permission
    {
        return Permission::MANAGE_SETTINGS();
    }

    public function getTitle(Request $request): string
    {
        return $this->lang->t("payment_platforms");
    }

    public function getContent(Request $request)
    {
        $pagination = $this->paginationFactory->create($request);

        $addButton = new Input();
        $addButton->setParam("id", "payment_platform_button_add");
        $addButton->setParam("type", "button");
        $addButton->addClass("button");
        $addButton->setParam("value", $this->lang->t("add_payment_platform"));

        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS * FROM `ss_payment_platforms` LIMIT ?, ?"
        );
        $statement->execute($pagination->getSqlLimit());
        $rowsCount = $this->db->query("SELECT FOUND_ROWS()")->fetchColumn();

        $bodyRows = collect($statement)
            ->map(fn(array $row) => $this->paymentPlatformRepository->mapToModel($row))
            ->map(
                fn(PaymentPlatform $paymentPlatform) => (new BodyRow())
                    ->setDbId($paymentPlatform->getId())
                    ->addCell(new Cell($paymentPlatform->getName(), "name"))
                    ->addCell(new Cell($this->lang->t($paymentPlatform->getModuleId())))
                    ->setEditAction(true)
                    ->setDeleteAction(true)
            )
            ->all();

        $table = (new Structure())
            ->addHeadCell(new HeadCell($this->lang->t("id"), "id"))
            ->addHeadCell(new HeadCell($this->lang->t("name")))
            ->addHeadCell(new HeadCell($this->lang->t("module")))
            ->addBodyRows($bodyRows)
            ->enablePagination($this->getPagePath(), $pagination, $rowsCount);

        return (new Wrapper())
            ->setTitle($this->getTitle($request))
            ->setTable($table)
            ->addButton($addButton);
    }

    public function getActionBox($boxId, array $query): string
    {
        if (cannot(Permission::MANAGE_SETTINGS())) {
            throw new UnauthorizedException();
        }

        if ($boxId === "create") {
            $paymentModules = collect($this->paymentModuleManager->allIds())
                ->map(
                    fn($paymentModuleId) => new Option(
                        $this->lang->t($paymentModuleId),
                        $paymentModuleId
                    )
                )
                ->join();

            return $this->template->render(
                "admin/action_boxes/payment_platform_add",
                compact("paymentModules")
            );
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

        throw new EntityNotFoundException();
    }
}
