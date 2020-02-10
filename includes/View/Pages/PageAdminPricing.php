<?php
namespace App\View\Pages;

use App\Exceptions\UnauthorizedException;
use App\Repositories\PriceRepository;
use App\Repositories\SmsPriceRepository;
use App\Services\PriceTextService;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\HeadCell;
use App\View\Html\Input;
use App\View\Html\Structure;
use App\View\Html\Wrapper;
use App\View\Pages\Interfaces\IPageAdminActionBox;

class PageAdminPricing extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = 'pricing';
    protected $privilege = 'manage_settings';

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

        $this->heart->pageTitle = $this->title = $this->lang->t('pricing');
        $this->priceRepository = $priceRepository;
        $this->smsPriceRepository = $smsPriceRepository;
        $this->priceTextService = $priceTextService;
    }

    protected function content(array $query, array $body)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();
        $table->addHeadCell(new HeadCell($this->lang->t('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->t('service')));
        $table->addHeadCell(new HeadCell($this->lang->t('server')));
        $table->addHeadCell(new HeadCell($this->lang->t('quantity')));
        $table->addHeadCell(new HeadCell($this->lang->t('sms_price')));
        $table->addHeadCell(new HeadCell($this->lang->t('transfer_price')));

        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS * " .
                "FROM `ss_prices` " .
                "ORDER BY `service`, `server`, `quantity` " .
                "LIMIT ?"
        );
        $statement->execute([get_row_limit($this->currentPage->getPageNumber())]);

        $table->setDbRowsCount($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

        foreach ($statement as $row) {
            $price = $this->priceRepository->mapToModel($row);
            $bodyRow = new BodyRow();

            if ($price->isForEveryServer()) {
                $serverName = $this->lang->t('all_servers');
            } else {
                $server = $this->heart->getServer($price->getServerId());
                $serverName = $server ? $server->getName() : "n/a";
            }

            $service = $this->heart->getService($price->getServiceId());
            $serviceName = $service ? "{$service->getName()} ( {$service->getId()} )" : "n/a";
            $quantity = $price->isForever() ? $this->lang->t("forever") : $price->getQuantity();
            $smsPrice = $price->hasSmsPrice()
                ? $this->priceTextService->getPriceGrossText($price->getSmsPrice())
                : "n/a";
            $transferPrice = $price->hasTransferPrice()
                ? $this->priceTextService->getPriceText($price->getTransferPrice())
                : "n/a";

            $bodyRow->setDbId($price->getId());
            $bodyRow->addCell(new Cell($serviceName));
            $bodyRow->addCell(new Cell($serverName));
            $bodyRow->addCell(new Cell($quantity));
            $bodyRow->addCell(new Cell($smsPrice));
            $bodyRow->addCell(new Cell($transferPrice));

            $bodyRow->setDeleteAction(true);
            $bodyRow->setEditAction(true);

            $table->addBodyRow($bodyRow);
        }

        $wrapper->setTable($table);

        $button = new Input();
        $button->setParam('id', 'price_button_add');
        $button->setParam('type', 'button');
        $button->addClass('button');
        $button->setParam('value', $this->lang->t('add_price'));
        $wrapper->addButton($button);

        return $wrapper->toHtml();
    }

    public function getActionBox($boxId, array $query)
    {
        if (!get_privileges("manage_settings")) {
            throw new UnauthorizedException();
        }

        if ($boxId == "price_edit") {
            $price = $this->priceRepository->getOrFail($query['id']);
            $allServers = $price->isForEveryServer() ? "selected" : "";
        }

        $services = "";
        foreach ($this->heart->getServices() as $serviceId => $service) {
            $services .= create_dom_element(
                "option",
                $service->getName() . " ( " . $service->getId() . " )",
                [
                    'value' => $service->getId(),
                    'selected' =>
                        isset($price) && $price->getServiceId() === $service->getId()
                            ? "selected"
                            : "",
                ]
            );
        }

        $servers = "";
        foreach ($this->heart->getServers() as $serverId => $server) {
            $servers .= create_dom_element("option", $server->getName(), [
                'value' => $server->getId(),
                'selected' =>
                    isset($price) && $price->getServerId() === $server->getId() ? "selected" : "",
            ]);
        }

        $smsPrices = "";
        foreach ($this->smsPriceRepository->all() as $smsPrice) {
            $smsPrices .= create_dom_element(
                "option",
                $this->priceTextService->getPriceGrossText($smsPrice),
                [
                    'value' => $smsPrice,
                    'selected' =>
                        isset($price) && $price->getSmsPrice() === $smsPrice ? "selected" : "",
                ]
            );
        }

        switch ($boxId) {
            case "price_add":
                $output = $this->template->render(
                    "admin/action_boxes/price_add",
                    compact('services', 'servers', 'smsPrices')
                );
                break;

            case "price_edit":
                $transferPrice = $price->hasTransferPrice()
                    ? $price->getTransferPrice() / 100
                    : null;
                $output = $this->template->render(
                    "admin/action_boxes/price_edit",
                    compact(
                        'services',
                        'servers',
                        'smsPrices',
                        'price',
                        'transferPrice',
                        'allServers'
                    )
                );
                break;

            default:
                $output = '';
        }

        return [
            'status' => 'ok',
            'template' => $output,
        ];
    }
}
