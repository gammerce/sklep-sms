<?php
namespace App\View\Pages;

use App\Exceptions\UnauthorizedException;
use App\Repositories\PricelistRepository;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\HeadCell;
use App\View\Html\Input;
use App\View\Html\Structure;
use App\View\Html\Wrapper;
use App\View\Pages\Interfaces\IPageAdminActionBox;

class PageAdminPriceList extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = 'pricelist';
    protected $privilege = 'manage_settings';

    /** @var PricelistRepository */
    private $priceRepository;

    public function __construct(PricelistRepository $priceRepository)
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t('pricelist');
        $this->priceRepository = $priceRepository;
    }

    protected function content(array $query, array $body)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();
        $table->addHeadCell(new HeadCell($this->lang->t('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->t('service')));
        $table->addHeadCell(new HeadCell($this->lang->t('tariff')));
        $table->addHeadCell(new HeadCell($this->lang->t('amount')));
        $table->addHeadCell(new HeadCell($this->lang->t('server')));

        $result = $this->db->query(
            "SELECT SQL_CALC_FOUND_ROWS * " .
                "FROM `" .
                TABLE_PREFIX .
                "pricelist` " .
                "ORDER BY `service`, `server`, `tariff` " .
                "LIMIT " .
                get_row_limit($this->currentPage->getPageNumber())
        );

        $table->setDbRowsAmount($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

        foreach ($result as $row) {
            $bodyRow = new BodyRow();

            if ($row['server'] != -1) {
                $server = $this->heart->getServer($row['server']);
                $serverName = $server ? $server->getName() : "n/a";
            } else {
                $serverName = $this->lang->t('all_servers');
            }

            $service = $this->heart->getService($row['service']);
            $serviceName = $service ? "{$service->getName()} ( {$service->getId()} )" : "n/a";

            $bodyRow->setDbId($row['id']);
            $bodyRow->addCell(new Cell($serviceName));
            $bodyRow->addCell(new Cell($row['tariff']));
            $bodyRow->addCell(new Cell($row['amount']));
            $bodyRow->addCell(new Cell($serverName));

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
            $allServers = $price->getServer() == -1 ? "selected" : "";
        }

        // Pobranie usług
        $services = "";
        foreach ($this->heart->getServices() as $serviceId => $service) {
            $services .= create_dom_element(
                "option",
                $service->getName() . " ( " . $service->getId() . " )",
                [
                    'value' => $service->getId(),
                    'selected' =>
                        isset($price) && $price['service'] == $service->getId() ? "selected" : "",
                ]
            );
        }

        // Pobranie serwerów
        $servers = "";
        foreach ($this->heart->getServers() as $serverId => $server) {
            $servers .= create_dom_element("option", $server->getName(), [
                'value' => $server->getId(),
                'selected' =>
                    isset($price) && $price['server'] == $server->getId() ? "selected" : "",
            ]);
        }

        // Pobranie taryf
        $tariffs = "";
        foreach ($this->heart->getTariffs() as $tariff) {
            $tariffs .= create_dom_element("option", $tariff->getId(), [
                'value' => $tariff->getId(),
                'selected' =>
                    isset($price) && $price['tariff'] == $tariff->getId() ? "selected" : "",
            ]);
        }

        switch ($boxId) {
            case "price_add":
                $output = $this->template->render(
                    "admin/action_boxes/price_add",
                    compact('services', 'servers', 'tariffs')
                );
                break;

            case "price_edit":
                $output = $this->template->render(
                    "admin/action_boxes/price_edit",
                    compact('services', 'servers', 'tariffs', 'price', 'allServers')
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
