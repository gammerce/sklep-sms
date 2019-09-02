<?php
namespace App\Pages;

use Admin\Table\BodyRow;
use Admin\Table\Cell;
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

        $cell = new Cell($this->lang->translate('id'));
        $cell->setParam('headers', 'id');
        $table->addHeadCell($cell);

        $table->addHeadCell(new Cell($this->lang->translate('service')));
        $table->addHeadCell(new Cell($this->lang->translate('tariff')));
        $table->addHeadCell(new Cell($this->lang->translate('amount')));
        $table->addHeadCell(new Cell($this->lang->translate('server')));

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
            $body_row = new BodyRow();

            $service = $this->heart->getService($row['service']);

            if ($row['server'] != -1) {
                $temp_server = $this->heart->getServer($row['server']);
                $server_name = $temp_server['name'];
                unset($temp_server);
            } else {
                $server_name = $this->lang->translate('all_servers');
            }

            $body_row->setDbId($row['id']);
            $body_row->addCell(new Cell("{$service['name']} ( {$service['id']} )"));
            $body_row->addCell(new Cell($row['tariff']));
            $body_row->addCell(new Cell($row['amount']));
            $body_row->addCell(new Cell($server_name));

            $body_row->setButtonDelete(true);
            $body_row->setButtonEdit(true);

            $table->addBodyRow($body_row);
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
        foreach ($this->heart->getServices() as $service_id => $service) {
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
        foreach ($this->heart->getServers() as $server_id => $server) {
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
