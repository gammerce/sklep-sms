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
use App\Services\PriceTextService;
use App\Support\Database;
use App\Support\Template;
use App\Translation\TranslationManager;
use App\View\CurrentPage;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\HeadCell;
use App\View\Html\Input;
use App\View\Html\ServerRef;
use App\View\Html\ServiceRef;
use App\View\Html\Structure;
use App\View\Html\Wrapper;
use App\View\Pages\IPageAdminActionBox;
use Symfony\Component\HttpFoundation\Request;

class PageAdminPricing extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = "pricing";

    /** @var PriceRepository */
    private $priceRepository;

    /** @var SmsPriceRepository */
    private $smsPriceRepository;

    /** @var PriceTextService */
    private $priceTextService;

    /** @var CurrentPage */
    private $currentPage;

    /** @var Database */
    private $db;

    /** @var ServiceManager */
    private $serviceManager;

    /** @var ServerManager */
    private $serverManager;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        ServiceManager $serviceManager,
        ServerManager $serverManager,
        PriceRepository $priceRepository,
        SmsPriceRepository $smsPriceRepository,
        PriceTextService $priceTextService,
        CurrentPage $currentPage,
        Database $db
    ) {
        parent::__construct($template, $translationManager);

        $this->priceRepository = $priceRepository;
        $this->smsPriceRepository = $smsPriceRepository;
        $this->priceTextService = $priceTextService;
        $this->currentPage = $currentPage;
        $this->db = $db;
        $this->serviceManager = $serviceManager;
        $this->serverManager = $serverManager;
    }

    public function getPrivilege()
    {
        return "manage_settings";
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("pricing");
    }

    public function getContent(Request $request)
    {
        $statement = $this->db->statement(
            <<<EOF
SELECT SQL_CALC_FOUND_ROWS * 
FROM `ss_prices` 
ORDER BY `service_id`, `server_id`, `quantity` 
LIMIT ?, ?
EOF
        );
        $statement->execute(get_row_limit($this->currentPage->getPageNumber()));
        $rowsCount = $this->db->query("SELECT FOUND_ROWS()")->fetchColumn();

        $bodyRows = collect($statement)
            ->map(function (array $row) {
                return $this->priceRepository->mapToModel($row);
            })
            ->map(function (Price $price) {
                if ($price->isForEveryServer()) {
                    $serverEntry = $this->lang->t("all");
                } else {
                    $server = $this->serverManager->getServer($price->getServerId());
                    $serverEntry = $server
                        ? new ServerRef($server->getId(), $server->getName())
                        : "n/a";
                }

                $service = $this->serviceManager->getService($price->getServiceId());
                $serviceEntry = $service
                    ? new ServiceRef($service->getId(), $service->getName())
                    : "n/a";

                $quantity = $price->isForever() ? $this->lang->t("forever") : $price->getQuantity();
                $smsPrice = $price->hasSmsPrice()
                    ? $this->priceTextService->getPriceGrossText($price->getSmsPrice())
                    : "n/a";
                $transferPrice = $price->hasTransferPrice()
                    ? $this->priceTextService->getPriceText($price->getTransferPrice())
                    : "n/a";
                $directBillingPrice = $price->hasDirectBillingPrice()
                    ? $this->priceTextService->getPriceText($price->getDirectBillingPrice())
                    : "n/a";

                return (new BodyRow())
                    ->setDbId($price->getId())
                    ->addCell(new Cell($serviceEntry))
                    ->addCell(new Cell($serverEntry))
                    ->addCell(new Cell($quantity))
                    ->addCell(new Cell($smsPrice))
                    ->addCell(new Cell($transferPrice))
                    ->addCell(new Cell($directBillingPrice))
                    ->setDeleteAction(true)
                    ->setEditAction(true);
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
            ->enablePagination($this->getPagePath(), $request->query->all(), $rowsCount);

        return (new Wrapper())
            ->setTitle($this->getTitle($request))
            ->setTable($table)
            ->addButton($this->createAddButton())
            ->toHtml();
    }

    private function createAddButton()
    {
        return (new Input())
            ->setParam("id", "price_button_add")
            ->setParam("type", "button")
            ->addClass("button")
            ->setParam("value", $this->lang->t("add_price"));
    }

    public function getActionBox($boxId, array $query)
    {
        if (!has_privileges("manage_settings")) {
            throw new UnauthorizedException();
        }

        if ($boxId == "edit") {
            $price = $this->priceRepository->getOrFail($query["id"]);
        } else {
            $price = null;
        }

        $services = collect($this->serviceManager->getServices())
            ->map(function (Service $service) use ($price) {
                return create_dom_element(
                    "option",
                    "{$service->getName()} ( {$service->getId()} )",
                    [
                        "value" => $service->getId(),
                        "selected" =>
                            $price && $price->getServiceId() === $service->getId()
                                ? "selected"
                                : "",
                    ]
                );
            })
            ->join();

        $servers = collect($this->serverManager->getServers())
            ->map(function (Server $server) use ($price) {
                return create_dom_element("option", $server->getName(), [
                    "value" => $server->getId(),
                    "selected" =>
                        $price && $price->getServerId() === $server->getId() ? "selected" : "",
                ]);
            })
            ->join();

        $smsPrices = collect($this->smsPriceRepository->all())
            ->map(function ($smsPrice) use ($price) {
                return create_dom_element(
                    "option",
                    $this->priceTextService->getPriceGrossText($smsPrice),
                    [
                        "value" => $smsPrice,
                        "selected" =>
                            $price && $price->getSmsPrice() === $smsPrice ? "selected" : "",
                    ]
                );
            })
            ->join();

        switch ($boxId) {
            case "add":
                return $this->template->render(
                    "admin/action_boxes/price_add",
                    compact("services", "servers", "smsPrices")
                );

            case "edit":
                $directBillingPrice = $price->hasDirectBillingPrice()
                    ? $price->getDirectBillingPrice() / 100
                    : null;
                $transferPrice = $price->hasTransferPrice()
                    ? $price->getTransferPrice() / 100
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
                        "allServers" => $price->isForEveryServer() ? "selected" : "",
                        "discount" => $price->getDiscount(),
                    ]
                );

            default:
                throw new EntityNotFoundException();
        }
    }
}
