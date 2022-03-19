<?php
namespace App\View\Pages\Admin;

use App\Exceptions\EntityNotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Managers\ServerManager;
use App\Managers\ServiceManager;
use App\Models\Price;
use App\Models\Server;
use App\Models\Service;
use App\Repositories\PriceRepository;
use App\Repositories\SmsPriceRepository;
use App\Support\Database;
use App\Support\Money;
use App\Support\PriceTextService;
use App\Theme\Template;
use App\Translation\TranslationManager;
use App\User\Permission;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\DOMElement;
use App\View\Html\HeadCell;
use App\View\Html\Input;
use App\View\Html\NoneText;
use App\View\Html\Option;
use App\View\Html\ServerRef;
use App\View\Html\ServiceRef;
use App\View\Html\Structure;
use App\View\Html\Wrapper;
use App\View\Pages\IPageAdminActionBox;
use App\View\Pagination\PaginationFactory;
use Symfony\Component\HttpFoundation\Request;

class PageAdminPricing extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = "pricing";

    private PriceRepository $priceRepository;
    private SmsPriceRepository $smsPriceRepository;
    private PriceTextService $priceTextService;
    private Database $db;
    private ServiceManager $serviceManager;
    private ServerManager $serverManager;
    private PaginationFactory $paginationFactory;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        ServiceManager $serviceManager,
        ServerManager $serverManager,
        PriceRepository $priceRepository,
        SmsPriceRepository $smsPriceRepository,
        PriceTextService $priceTextService,
        Database $db,
        PaginationFactory $paginationFactory
    ) {
        parent::__construct($template, $translationManager);

        $this->priceRepository = $priceRepository;
        $this->smsPriceRepository = $smsPriceRepository;
        $this->priceTextService = $priceTextService;
        $this->db = $db;
        $this->serviceManager = $serviceManager;
        $this->serverManager = $serverManager;
        $this->paginationFactory = $paginationFactory;
    }

    public function getPrivilege(): Permission
    {
        return Permission::PRICING_VIEW();
    }

    public function getTitle(Request $request = null): string
    {
        return $this->lang->t("pricing");
    }

    public function getContent(Request $request)
    {
        $pagination = $this->paginationFactory->create($request);

        $statement = $this->db->statement(
            <<<EOF
SELECT SQL_CALC_FOUND_ROWS * 
FROM `ss_prices` 
ORDER BY `service_id`, `server_id`, `quantity` 
LIMIT ?, ?
EOF
        );
        $statement->execute($pagination->getSqlLimit());
        $rowsCount = $this->db->query("SELECT FOUND_ROWS()")->fetchColumn();

        $bodyRows = collect($statement)
            ->map(fn(array $row) => $this->priceRepository->mapToModel($row))
            ->map(function (Price $price) {
                if ($price->isForEveryServer()) {
                    $serverEntry = $this->lang->t("all");
                } else {
                    $server = $this->serverManager->get($price->getServerId());
                    $serverEntry = $server
                        ? new ServerRef($server->getId(), $server->getName())
                        : new NoneText();
                }

                $service = $this->serviceManager->get($price->getServiceId());
                $serviceEntry = $service
                    ? new ServiceRef($service->getId(), $service->getName())
                    : new NoneText();

                $quantity = $price->isForever() ? $this->lang->t("forever") : $price->getQuantity();
                $smsPrice = $price->hasSmsPrice()
                    ? $this->priceTextService->getPriceGrossText($price->getSmsPrice())
                    : new NoneText();
                $transferPrice = $price->hasTransferPrice()
                    ? $this->priceTextService->getPriceText($price->getTransferPrice())
                    : new NoneText();
                $directBillingPrice = $price->hasDirectBillingPrice()
                    ? $this->priceTextService->getPriceText($price->getDirectBillingPrice())
                    : new NoneText();

                return (new BodyRow())
                    ->setDbId($price->getId())
                    ->addCell(new Cell($serviceEntry))
                    ->addCell(new Cell($serverEntry))
                    ->addCell(new Cell($quantity))
                    ->addCell(new Cell($smsPrice))
                    ->addCell(new Cell($transferPrice))
                    ->addCell(new Cell($directBillingPrice))
                    ->when(
                        can(Permission::PRICING_MANAGEMENT()),
                        fn(BodyRow $bodyRow) => $bodyRow->setDeleteAction(true)->setEditAction(true)
                    );
            })
            ->all();

        $table = (new Structure())
            ->addHeadCell(new HeadCell($this->lang->t("id"), "id"))
            ->addHeadCell(new HeadCell($this->lang->t("service")))
            ->addHeadCell(new HeadCell($this->lang->t("server")))
            ->addHeadCell(new HeadCell($this->lang->t("quantity")))
            ->addHeadCell(new HeadCell($this->lang->t("sms_price")))
            ->addHeadCell(new HeadCell($this->lang->t("transfer_price")))
            ->addHeadCell(new HeadCell($this->lang->t("direct_billing_price")))
            ->addBodyRows($bodyRows)
            ->enablePagination($this->getPagePath(), $pagination, $rowsCount);

        $wrapper = (new Wrapper())->setTitle($this->getTitle($request))->setTable($table);

        if (can(Permission::PRICING_MANAGEMENT())) {
            $wrapper->addButton($this->createAddButton());
        }

        return $wrapper->toHtml();
    }

    private function createAddButton(): DOMElement
    {
        return (new Input())
            ->setParam("id", "price_button_add")
            ->setParam("type", "button")
            ->addClass("button")
            ->setParam("value", $this->lang->t("add_price"));
    }

    public function getActionBox($boxId, array $query): string
    {
        if (cannot(Permission::PRICING_MANAGEMENT())) {
            throw new UnauthorizedException();
        }

        if ($boxId == "edit") {
            $price = $this->priceRepository->getOrFail($query["id"]);
        } else {
            $price = null;
        }

        $services = collect($this->serviceManager->all())
            ->map(function (Service $service) use ($price) {
                $selected = $price && $price->getServiceId() === $service->getId();
                return new Option(
                    "{$service->getName()} ({$service->getId()})",
                    $service->getId(),
                    [
                        "selected" => selected($selected),
                    ]
                );
            })
            ->join();

        $servers = collect($this->serverManager->all())
            ->map(
                fn(Server $server) => new Option($server->getName(), $server->getId(), [
                    "selected" => selected($price && $price->getServerId() === $server->getId()),
                ])
            )
            ->join();

        $smsPrices = collect($this->smsPriceRepository->all())
            ->map(
                fn(Money $smsPrice) => new Option(
                    $this->priceTextService->getPriceGrossText($smsPrice),
                    $smsPrice->asInt(),
                    [
                        "selected" => selected($price && $smsPrice->equal($price->getSmsPrice())),
                    ]
                )
            )
            ->join();

        switch ($boxId) {
            case "add":
                return $this->template->render(
                    "admin/action_boxes/price_add",
                    compact("services", "servers", "smsPrices")
                );

            case "edit":
                $directBillingPrice = $price->hasDirectBillingPrice()
                    ? $price->getDirectBillingPrice()->asFloat()
                    : null;
                $transferPrice = $price->hasTransferPrice()
                    ? $price->getTransferPrice()->asFloat()
                    : null;

                return $this->template->render(
                    "admin/action_boxes/price_edit",
                    compact(
                        "directBillingPrice",
                        "price",
                        "servers",
                        "services",
                        "smsPrices",
                        "transferPrice"
                    ) + [
                        "allServers" => selected($price->isForEveryServer()),
                        "discount" => $price->getDiscount(),
                    ]
                );

            default:
                throw new EntityNotFoundException();
        }
    }
}
