<?php
namespace App\Pages;

use Admin\Table\BodyRow;
use Admin\Table\Cell;
use Admin\Table\Input;
use Admin\Table\Structure;
use Admin\Table\Wrapper;
use App\Pages\Interfaces\IPageAdminActionBox;

class PageAdminServers extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = 'servers';
    protected $privilage = 'manage_servers';

    public function __construct()
    {
        parent::__construct();

        $this->heart->page_title = $this->title = $this->lang->translate('servers');
    }

    protected function content($get, $post)
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

        foreach ($this->heart->get_servers() as $row) {
            $body_row = new BodyRow();

            $body_row->setDbId($row['id']);
            $body_row->addCell(new Cell(htmlspecialchars($row['name'])));
            $body_row->addCell(new Cell(htmlspecialchars($row['ip'] . ':' . $row['port'])));
            $body_row->addCell(new Cell(htmlspecialchars($row['version'])));

            if (get_privilages("manage_servers")) {
                $body_row->setButtonDelete(true);
                $body_row->setButtonEdit(true);
            }

            $table->addBodyRow($body_row);
        }

        $wrapper->setTable($table);

        if (get_privilages("manage_servers")) {
            $button = new Input();
            $button->setParam('id', 'server_button_add');
            $button->setParam('type', 'button');
            $button->setParam('value', $this->lang->translate('add_server'));
            $wrapper->addButton($button);
        }

        return $wrapper->toHtml();
    }

    public function get_action_box($box_id, $data)
    {
        if (!get_privilages("manage_servers")) {
            return [
                'status' => "not_logged_in",
                'text' => $this->lang->translate('not_logged_or_no_perm'),
            ];
        }

        if ($box_id == "server_edit") {
            $server = $this->heart->get_server($data['id']);
            $server['ip'] = htmlspecialchars($server['ip']);
            $server['port'] = htmlspecialchars($server['port']);
        }

        // Pobranie listy serwisów transakcyjnych
        $result = $this->db->query(
            "SELECT `id`, `name`, `sms` " . "FROM `" . TABLE_PREFIX . "transaction_services`"
        );
        $sms_services = "";
        while ($row = $this->db->fetch_array_assoc($result)) {
            if (!$row['sms']) {
                continue;
            }

            $sms_services .= create_dom_element("option", $row['name'], [
                'value' => $row['id'],
                'selected' => $row['id'] == $server['sms_service'] ? "selected" : "",
            ]);
        }

        $services = "";
        foreach ($this->heart->get_services() as $service) {
            // Dana usługa nie może być kupiona na serwerze
            if (
                ($service_module = $this->heart->get_service_module($service['id'])) === null ||
                !($service_module instanceof IService_AvailableOnServers)
            ) {
                continue;
            }

            $values = create_dom_element(
                "option",
                $this->lang->strtoupper($this->lang->translate('no')),
                [
                    'value' => 0,
                    'selected' => $this->heart->server_service_linked($server['id'], $service['id'])
                        ? ""
                        : "selected",
                ]
            );

            $values .= create_dom_element(
                "option",
                $this->lang->strtoupper($this->lang->translate('yes')),
                [
                    'value' => 1,
                    'selected' => $this->heart->server_service_linked($server['id'], $service['id'])
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

        switch ($box_id) {
            case "server_add":
                $output = $this->template->render(
                    "admin/action_boxes/server_add",
                    compact('sms_services', 'services')
                );
                break;

            case "server_edit":
                $output = $this->template->render(
                    "admin/action_boxes/server_edit",
                    compact('server', 'sms_services', 'services')
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
