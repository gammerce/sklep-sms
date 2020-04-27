<?php
namespace App\View\Pages;

use App\Exceptions\UnauthorizedException;
use App\Models\Price;
use App\Models\Server;
use App\Models\Service;
use App\Repositories\PriceRepository;
use App\Repositories\SmsPriceRepository;
use App\Services\PriceTextService;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\HeadCell;
use App\View\Html\Input;
use App\View\Html\ServerRef;
use App\View\Html\ServiceRef;
use App\View\Html\Structure;
use App\View\Html\Wrapper;
use App\View\Pages\Interfaces\IPageAdminActionBox;
use Symfony\Component\HttpFoundation\Request;

class PageAdminPricing extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = "pricing";
    protected $privilege = "manage_settings";

    /** @var PriceRepository */
    private $priceRepository;

    /** @var SmsPriceRepository */
    private $smsPriceRepository;

    /** @var PriceTextService */
    private $priceTextService;

    public function __construct(
        PriceRepository $priceRepository,
        SmsPriceRepository $smsPriceRepository,
        PriceTextService $priceTextService
    ) {
        parent::__construct();

        $this->priceRepository = $priceRepository;
        $this->smsPriceRepository = $smsPriceRepository;
        $this->priceTextService = $priceTextService;
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("pricing");
    }

    protected function content(array $query, array $body)
    {
        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS * " .
                "FROM `ss_prices` " .
                "ORDER BY `service`, `server`, `quantity` " .
                "LIMIT ?, ?"
        );
        $statement->execute(get_row_limit($this->currentPage->getPageNumber()));
        $rowsCount = $this->db->query('SELECT FOUND_ROWS()')->fetchColumn();

        $bodyRows = collect($statement)
            ->map(function (array $row) {
                return $this->priceRepository->mapToModel($row);
            })
            ->map(function (Price $price) {
                if ($price->isForEveryServer()) {
                    $serverEntry = $this->lang->t("all_servers");
                } else {
                    $server = $this->heart->getServer($price->getServerId());
                    $serverEntry = $server
                        ? new ServerRef($server->getId(), $server->getName())
                        : "n/a";
                }

                $service = $this->heart->getService($price->getServiceId());
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
            ->enablePagination($this->getPagePath(), $query, $rowsCount);

        return (new Wrapper())
            ->setTitle($this->title)
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

        $price = null;
        if ($boxId == "price_edit") {
            $price = $this->priceRepository->getOrFail($query["id"]);
        }

        $services = collect($this->heart->getServices())
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

        $servers = collect($this->heart->getServers())
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
            case "price_add":
                $output = $this->template->render(
                    "admin/action_boxes/price_add",
                    compact("services", "servers", "smsPrices")
                );
                break;

            case "price_edit":
                $directBillingPrice = $price->hasDirectBillingPrice()
                    ? $price->getDirectBillingPrice() / 100
                    : null;
                $transferPrice = $price->hasTransferPrice()
                    ? $price->getTransferPrice() / 100
                    : null;

                $output = $this->template->render(
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
                break;

            default:
                $output = "";
        }

        return [
            "status" => "ok",
            "template" => $output,
        ];
    }
}
