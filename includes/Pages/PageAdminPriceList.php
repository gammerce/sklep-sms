<?php
namespace App\Pages;

use Admin\Table\BodyRow;
use Admin\Table\Cell;
use Admin\Table\HeadCell;
use Admin\Table\Input;
use Admin\Table\Structure;
use Admin\Table\Wrapper;
use App\Pages\Interfaces\IPageAdminActionBox;

class PageAdminPriceList extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = 'pricelist';
    protected $privilege = 'manage_settings';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->translate('pricelist');
    }

    protected function content(array $query, array $body)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();
        $table->addHeadCell(new HeadCell($this->lang->translate('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->translate('service')));
        $table->addHeadCell(new HeadCell($this->lang->translate('tariff')));
        $table->addHeadCell(new HeadCell($this->lang->translate('amount')));
        $table->addHeadCell(new HeadCell($this->lang->translate('server')));

        $result = $this->db->query(
            "SELECT SQL_CALC_FOUND_ROWS * " .
                "FROM `" .
                TABLE_PREFIX .
                "pricelist` " .
                "ORDER BY `service`, `server`, `tariff` " .
                "LIMIT " .
                get_row_limit($this->currentPage->getPageNumber())
        );

        $table->setDbRowsAmount($this->db->getColumn("SELECT FOUND_ROWS()", "FOUND_ROWS()"));

        while ($row = $this->db->fetchArrayAssoc($result)) {
            $bodyRow = new BodyRow();

            $service = $this->heart->getService($row['service']);

            if ($row['server'] != -1) {
                $tmpServer = $this->heart->getServer($row['server']);
                $serverName = $tmpServer['name'];
                unset($tmpServer);
            } else {
                $serverName = $this->lang->translate('all_servers');
            }

            $bodyRow->setDbId($row['id']);
            $bodyRow->addCell(new Cell("{$service['name']} ( {$service['id']} )"));
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
        $button->setParam('class', 'button');
        $button->setParam('value', $this->lang->translate('add_price'));
        $wrapper->addButton($button);

        return $wrapper->toHtml();
    }

    public function getActionBox($boxId, $data)
    {
        if (!get_privileges("manage_settings")) {
            return [
                'status' => "not_logged_in",
                'text' => $this->lang->translate('not_logged_or_no_perm'),
            ];
        }

        if ($boxId == "price_edit") {
            $result = $this->db->query(
                $this->db->prepare(
                    "SELECT * FROM `" . TABLE_PREFIX . "pricelist` " . "WHERE `id` = '%d'",
                    [$data['id']]
                )
            );
            $price = $this->db->fetchArrayAssoc($result);

            $allServers = $price['server'] == -1 ? "selected" : "";
        }

        // Pobranie usług
        $services = "";
        foreach ($this->heart->getServices() as $serviceId => $service) {
            $services .= create_dom_element(
                "option",
                $service['name'] . " ( " . $service['id'] . " )",
                [
                    'value' => $service['id'],
                    'selected' =>
                        isset($price) && $price['service'] == $service['id'] ? "selected" : "",
                ]
            );
        }

        // Pobranie serwerów
        $servers = "";
        foreach ($this->heart->getServers() as $serverId => $server) {
            $servers .= create_dom_element("option", $server['name'], [
                'value' => $server['id'],
                'selected' => isset($price) && $price['server'] == $server['id'] ? "selected" : "",
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
