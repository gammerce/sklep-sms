<?php

use Admin\Table\BodyRow;
use Admin\Table\Cell;
use Admin\Table\Input;
use Admin\Table\Structure;
use Admin\Table\Wrapper;

class PageAdminServers extends PageAdmin implements IPageAdmin_ActionBox
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
        global $heart, $lang;

        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();

        $cell = new Cell($lang->translate('id'));
        $cell->setParam('headers', 'id');
        $table->addHeadCell($cell);

        $table->addHeadCell(new Cell($lang->translate('name')));
        $table->addHeadCell(new Cell($lang->translate('ip') . ':' . $lang->translate('port')));
        $table->addHeadCell(new Cell($lang->translate('version')));

        foreach ($heart->get_servers() as $row) {
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
            $button->setParam('value', $lang->translate('add_server'));
            $wrapper->addButton($button);
        }

        return $wrapper->toHtml();
    }

    public function get_action_box($box_id, $data)
    {
        global $heart, $db, $lang, $templates;

        if (!get_privilages("manage_servers")) {
            return [
                'status' => "not_logged_in",
                'text'   => $lang->translate('not_logged_or_no_perm'),
            ];
        }

        if ($box_id == "server_edit") {
            $server = $heart->get_server($data['id']);
            $server['ip'] = htmlspecialchars($server['ip']);
            $server['port'] = htmlspecialchars($server['port']);
        }

        // Pobranie listy serwisów transakcyjnych
        $result = $db->query(
            "SELECT `id`, `name`, `sms` " .
            "FROM `" . TABLE_PREFIX . "transaction_services`"
        );
        $sms_services = "";
        while ($row = $db->fetch_array_assoc($result)) {
            if (!$row['sms']) {
                continue;
            }

            $sms_services .= create_dom_element("option", $row['name'], [
                'value'    => $row['id'],
                'selected' => $row['id'] == $server['sms_service'] ? "selected" : "",
            ]);
        }

        $services = "";
        foreach ($heart->get_services() as $service) {
            // Dana usługa nie może być kupiona na serwerze
            if (($service_module = $heart->get_service_module($service['id'])) === null || !object_implements($service_module,
                    "IService_AvailableOnServers")) {
                continue;
            }

            $values = create_dom_element("option", $lang->strtoupper($lang->translate('no')), [
                'value'    => 0,
                'selected' => $heart->server_service_linked($server['id'], $service['id']) ? "" : "selected",
            ]);

            $values .= create_dom_element("option", $lang->strtoupper($lang->translate('yes')), [
                'value'    => 1,
                'selected' => $heart->server_service_linked($server['id'], $service['id']) ? "selected" : "",
            ]);

            $name = htmlspecialchars($service['id']);
            $text = htmlspecialchars("{$service['name']} ( {$service['id']} )");

            $services .= eval($templates->render("tr_text_select"));
        }

        switch ($box_id) {
            case "server_add":
                $output = eval($templates->render("admin/action_boxes/server_add"));
                break;

            case "server_edit":
                $output = eval($templates->render("admin/action_boxes/server_edit"));
                break;
        }

        return [
            'status'   => 'ok',
            'template' => $output,
        ];
    }

}