<?php

use Admin\Table\BodyRow;
use Admin\Table\Cell;
use Admin\Table\Input;
use Admin\Table\Structure;
use Admin\Table\Wrapper;

class PageAdminServices extends PageAdmin implements IPageAdmin_ActionBox
{
    const PAGE_ID = 'services';
    protected $privilage = 'view_services';

    public function __construct()
    {
        parent::__construct();

        $this->heart->page_title = $this->title = $this->lang->translate('services');
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
        $table->addHeadCell(new Cell($this->lang->translate('short_description')));
        $table->addHeadCell(new Cell($this->lang->translate('description')));
        $table->addHeadCell(new Cell($this->lang->translate('order')));

        foreach ($this->heart->get_services() as $row) {
            $body_row = new BodyRow();

            $body_row->setDbId(htmlspecialchars($row['id']));

            $cell = new Cell(htmlspecialchars($row['name']));
            $cell->setParam('headers', 'name');
            $body_row->addCell($cell);
            $body_row->addCell(new Cell(htmlspecialchars($row['short_description'])));
            $body_row->addCell(new Cell(htmlspecialchars($row['description'])));
            $body_row->addCell(new Cell($row['order']));

            if (get_privilages('manage_services')) {
                $body_row->setButtonDelete(true);
                $body_row->setButtonEdit(true);
            }

            $table->addBodyRow($body_row);
        }

        $wrapper->setTable($table);

        if (get_privilages('manage_services')) {
            $button = new Input();
            $button->setParam('id', 'service_button_add');
            $button->setParam('type', 'button');
            $button->setParam('value', $this->lang->translate('add_service'));
            $wrapper->addButton($button);
        }

        return $wrapper->toHtml();
    }

    public function get_action_box($box_id, $data)
    {
        if (!get_privilages("manage_services")) {
            return [
                'status' => "not_logged_in",
                'text' => $this->lang->translate('not_logged_or_no_perm'),
            ];
        }

        if ($box_id == "service_edit") {
            $service = $this->heart->get_service($data['id']);
            $service['tag'] = htmlspecialchars($service['tag']);

            // Pobieramy pola danego modułu
            if (strlen($service['module'])) {
                if (
                    ($service_module = $this->heart->get_service_module($service['id'])) !== null &&
                   $service_module instanceof IService_AdminManage
                ) {
                    $extra_fields = create_dom_element(
                        "tbody",
                        $service_module->service_admin_extra_fields_get(),
                        [
                            'class' => 'extra_fields',
                        ]
                    );
                }
            }
        }
        // Pobranie dostępnych modułów usług
        elseif ($box_id == "service_add") {
            $services_modules = "";
            foreach ($this->heart->get_services_modules() as $module) {
                // Sprawdzamy czy dany moduł zezwala na tworzenie nowych usług, które będzie obsługiwał
                if (
                    ($service_module = $this->heart->get_service_module_s($module['id'])) ===
                        null ||
                    !($service_module instanceof IService_Create)
                ) {
                    continue;
                }

                $services_modules .= create_dom_element("option", $module['name'], [
                    'value' => $module['id'],
                    'selected' =>
                        isset($service['module']) && $service['module'] == $module['id']
                            ? "selected"
                            : "",
                ]);
            }
        }

        // Grupy
        $groups = "";
        foreach ($this->heart->get_groups() as $group) {
            $groups .= create_dom_element("option", "{$group['name']} ( {$group['id']} )", [
                'value' => $group['id'],
                'selected' =>
                    isset($service['groups']) && in_array($group['id'], $service['groups'])
                        ? "selected"
                        : "",
            ]);
        }

        switch ($box_id) {
            case "service_add":
                $output = $this->template->render(
                    "admin/action_boxes/service_add",
                    compact('groups', 'services_modules')
                );
                break;

            case "service_edit":
                $service_module_name = $this->heart->get_service_module_name($service['module']);

                $output = $this->template->render(
                    "admin/action_boxes/service_edit",
                    compact('service', 'groups', 'service_module_name', 'extra_fields')
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
