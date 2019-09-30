<?php
namespace App\Pages;

use Admin\Table\BodyRow;
use Admin\Table\Cell;
use Admin\Table\Input;
use Admin\Table\Structure;
use Admin\Table\Wrapper;
use App\Pages\Interfaces\IPageAdminActionBox;
use App\Services\Interfaces\IServiceAvailableOnServers;

class PageAdminServers extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = 'servers';
    protected $privilege = 'manage_servers';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->translate('servers');
    }

    protected function content(array $query, array $body)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();

        $cell = new Cell($this->lang->translate('id'));
        $cell->setParam('headers', 'id');
        $table->addHeadCell($cell);

        $table->addHeadCell(new Cell($this->lang->translate('name')));
        $table->addHeadCell(
            new Cell($this->lang->translate('ip') . ':' . $this->lang->translate('port'))
        );
        $table->addHeadCell(new Cell($this->lang->translate('version')));

        foreach ($this->heart->getServers() as $row) {
            $bodyRow = new BodyRow();

            $bodyRow->setDbId($row['id']);
            $bodyRow->addCell(new Cell(htmlspecialchars($row['name'])));
            $bodyRow->addCell(new Cell(htmlspecialchars($row['ip'] . ':' . $row['port'])));
            $bodyRow->addCell(new Cell(htmlspecialchars($row['version'])));

            if (get_privileges("manage_servers")) {
                $bodyRow->setDeleteAction(true);
                $bodyRow->setEditAction(true);
            }

            $table->addBodyRow($bodyRow);
        }

        $wrapper->setTable($table);

        if (get_privileges("manage_servers")) {
            $button = new Input();
            $button->setParam('id', 'server_button_add');
            $button->setParam('type', 'button');
            $button->setParam('class', 'button');
            $button->setParam('value', $this->lang->translate('add_server'));
            $wrapper->addButton($button);
        }

        return $wrapper->toHtml();
    }

    public function getActionBox($boxId, $data)
    {
        if (!get_privileges("manage_servers")) {
            return [
                'status' => "not_logged_in",
                'text' => $this->lang->translate('not_logged_or_no_perm'),
            ];
        }

        if ($boxId == "server_edit") {
            $server = $this->heart->getServer($data['id']);
            $server['ip'] = htmlspecialchars($server['ip']);
            $server['port'] = htmlspecialchars($server['port']);
        }

        // Pobranie listy serwisów transakcyjnych
        $result = $this->db->query(
            "SELECT `id`, `name`, `sms` " . "FROM `" . TABLE_PREFIX . "transaction_services`"
        );
        $smsServices = "";
        while ($row = $this->db->fetchArrayAssoc($result)) {
            if (!$row['sms']) {
                continue;
            }

            $smsServices .= create_dom_element("option", $row['name'], [
                'value' => $row['id'],
                'selected' => $row['id'] == $server['sms_service'] ? "selected" : "",
            ]);
        }

        $services = "";
        foreach ($this->heart->getServices() as $service) {
            // Dana usługa nie może być kupiona na serwerze
            if (
                ($serviceModule = $this->heart->getServiceModule($service['id'])) === null ||
                !($serviceModule instanceof IServiceAvailableOnServers)
            ) {
                continue;
            }

            $values = create_dom_element(
                "option",
                $this->lang->strtoupper($this->lang->translate('no')),
                [
                    'value' => 0,
                    'selected' => $this->heart->serverServiceLinked($server['id'], $service['id'])
                        ? ""
                        : "selected",
                ]
            );

            $values .= create_dom_element(
                "option",
                $this->lang->strtoupper($this->lang->translate('yes')),
                [
                    'value' => 1,
                    'selected' => $this->heart->serverServiceLinked($server['id'], $service['id'])
                        ? "selected"
                        : "",
                ]
            );

            $name = htmlspecialchars($service['id']);
            $text = htmlspecialchars("{$service['name']} ( {$service['id']} )");

            $services .= $this->template->render(
                "tr_text_select",
                compact('name', 'text', 'values')
            );
        }

        switch ($boxId) {
            case "server_add":
                $output = $this->template->render(
                    "admin/action_boxes/server_add",
                    compact('smsServices', 'services')
                );
                break;

            case "server_edit":
                $output = $this->template->render(
                    "admin/action_boxes/server_edit",
                    compact('server', 'smsServices', 'services')
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
