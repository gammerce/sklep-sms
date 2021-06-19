<?php
namespace App\View\Pages\Admin;

use App\Exceptions\EntityNotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Managers\ServerManager;
use App\Managers\ServiceManager;
use App\Models\PromoCode;
use App\Models\Server;
use App\Models\Service;
use App\PromoCode\QuantityType;
use App\Repositories\PromoCodeRepository;
use App\Support\Database;
use App\Theme\Template;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\User\Permission;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\DateTimeCell;
use App\View\Html\DOMElement;
use App\View\Html\ExpirationDateCell;
use App\View\Html\HeadCell;
use App\View\Html\Input;
use App\View\Html\Link;
use App\View\Html\Option;
use App\View\Html\Structure;
use App\View\Html\Wrapper;
use App\View\Pages\IPageAdminActionBox;
use App\View\Pagination\PaginationFactory;
use Symfony\Component\HttpFoundation\Request;
use UnexpectedValueException;

class PageAdminPromoCodes extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = "promo_codes";

    private Database $db;
    private ServiceManager $serviceManager;
    private ServerManager $serverManager;
    private PromoCodeRepository $promoCodeRepository;
    private Settings $settings;
    private PaginationFactory $paginationFactory;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        PromoCodeRepository $promoCodeRepository,
        Database $db,
        ServiceManager $serviceManager,
        ServerManager $serverManager,
        Settings $settings,
        PaginationFactory $paginationFactory
    ) {
        parent::__construct($template, $translationManager);
        $this->db = $db;
        $this->serviceManager = $serviceManager;
        $this->promoCodeRepository = $promoCodeRepository;
        $this->serverManager = $serverManager;
        $this->settings = $settings;
        $this->paginationFactory = $paginationFactory;
    }

    public function getPrivilege(): Permission
    {
        return Permission::VIEW_PROMO_CODES();
    }

    public function getTitle(Request $request): string
    {
        return $this->lang->t("promo_codes");
    }

    public function getContent(Request $request)
    {
        $pagination = $this->paginationFactory->create($request);

        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS *" . "FROM `ss_promo_codes` AS sc " . "LIMIT ?, ?"
        );
        $statement->execute($pagination->getSqlLimit());
        $rowsCount = $this->db->query("SELECT FOUND_ROWS()")->fetchColumn();

        $bodyRows = collect($statement)
            ->map(fn(array $row) => $this->promoCodeRepository->mapToModel($row))
            ->map(
                fn(PromoCode $promoCode) => (new BodyRow())
                    ->setDbId($promoCode->getId())
                    ->addCell(new Cell($promoCode->getCode()))
                    ->addCell(new Cell($promoCode->getQuantityFormatted()))
                    ->addCell(new Cell($promoCode->getRemainingUsage()))
                    ->addCell(new ExpirationDateCell($promoCode->getExpiresAt()))
                    ->addCell(new DateTimeCell($promoCode->getCreatedAt()))
                    ->addAction($this->createViewButton())
                    ->setDeleteAction(can(Permission::MANAGE_PROMO_CODES()))
            )
            ->all();

        $table = (new Structure())
            ->addHeadCell(new HeadCell($this->lang->t("id"), "id"))
            ->addHeadCell(new HeadCell($this->lang->t("code")))
            ->addHeadCell(new HeadCell($this->lang->t("quantity")))
            ->addHeadCell(new HeadCell($this->lang->t("remaining_usage")))
            ->addHeadCell(new HeadCell($this->lang->t("expire")))
            ->addHeadCell(new HeadCell($this->lang->t("created_at")))
            ->addBodyRows($bodyRows)
            ->enablePagination($this->getPagePath(), $pagination, $rowsCount);

        $wrapper = (new Wrapper())->setTitle($this->getTitle($request))->setTable($table);

        if (can(Permission::MANAGE_PROMO_CODES())) {
            $addButton = $this->createAddButton();
            $wrapper->addButton($addButton);
        }

        return $wrapper->toHtml();
    }

    private function createAddButton(): DOMElement
    {
        return (new Input())
            ->setParam("id", "promo_code_button_add")
            ->setParam("type", "button")
            ->addClass("button")
            ->setParam("value", $this->lang->t("add_code"));
    }

    private function createViewButton(): DOMElement
    {
        return (new Link($this->lang->t("view")))->addClass("dropdown-item view-action");
    }

    public function getActionBox($boxId, array $query): string
    {
        if (cannot(Permission::MANAGE_PROMO_CODES())) {
            throw new UnauthorizedException();
        }

        switch ($boxId) {
            case "add":
                $services = collect($this->serviceManager->all())
                    ->map(
                        fn(Service $service) => new Option($service->getName(), $service->getId())
                    )
                    ->join();

                $servers = collect($this->serverManager->all())
                    ->map(fn(Server $server) => new Option($server->getName(), $server->getId()))
                    ->join();

                $quantityTypes = collect(QuantityType::values())
                    ->map(
                        fn(QuantityType $quantityType) => new Option(
                            $this->getQuantityTypeName($quantityType),
                            $quantityType->getValue()
                        )
                    )
                    ->join();

                return $this->template->render(
                    "admin/action_boxes/promo_code_add",
                    compact("services", "servers", "quantityTypes")
                );

            case "view":
                $promoCode = $this->promoCodeRepository->get(array_get($query, "id"));

                return $this->template->render("admin/action_boxes/promo_code_view", [
                    "id" => $promoCode->getId(),
                    "code" => $promoCode->getCode(),
                    "quantity" => $promoCode->getQuantityFormatted(),
                    "usageCount" => $promoCode->getUsageCount(),
                    "usageLimit" => $promoCode->getUsageLimit(),
                    "serviceId" => $promoCode->getServiceId(),
                    "serverId" => $promoCode->getServerId(),
                    "userId" => $promoCode->getUserId(),
                    "expiresAt" => as_date_string($promoCode->getCreatedAt()),
                    "createdAt" => as_datetime_string($promoCode->getCreatedAt()),
                ]);

            default:
                throw new EntityNotFoundException();
        }
    }

    private function getQuantityTypeName(QuantityType $quantityType)
    {
        switch ($quantityType) {
            case QuantityType::FIXED():
                return $this->settings->getCurrency();

            case QuantityType::PERCENTAGE():
                return "%";

            default:
                throw new UnexpectedValueException();
        }
    }
}
