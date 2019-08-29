<?php
namespace App\Pages;

use Admin\Table\BodyRow;
use Admin\Table\Cell;
use Admin\Table\Input;
use Admin\Table\Structure;
use Admin\Table\Wrapper;
use App\Pages\Interfaces\IPageAdminActionBox;
use App\Services\Interfaces\IServiceAdminManage;
use App\Services\Interfaces\IServiceCreate;

class PageAdminServices extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = 'services';
    protected $privilege = 'view_services';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->translate('services');
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

        foreach ($this->heart->getServices() as $row) {
            $body_row = new BodyRow();

            $body_row->setDbId(htmlspecialchars($row['id']));

            $cell = new Cell(htmlspecialchars($row['name']));
            $cell->setParam('headers', 'name');
            $body_row->addCell($cell);
            $body_row->addCell(new Cell(htmlspecialchars($row['short_description'])));
            $body_row->addCell(new Cell(htmlspecialchars($row['description'])));
            $body_row->addCell(new Cell($row['order']));

            if (get_privileges('manage_services')) {
                $body_row->setButtonDelete(true);
                $body_row->setButtonEdit(true);
            }

            $table->addBodyRow($body_row);
        }

        $wrapper->setTable($table);

        if (get_privileges('manage_services')) {
            $button = new Input();
            $button->setParam('id', 'service_button_add');
            $button->setParam('type', 'button');
            $button->setParam('class', 'button');
            $button->setParam('value', $this->lang->translate('add_service'));
            $wrapper->addButton($button);
        }

        return $wrapper->toHtml();
    }

    public function getActionBox($boxId, $data)
    {
        if (!get_privileges("manage_services")) {
            return [
                'status' => "not_logged_in",
                'text' => $this->lang->translate('not_logged_or_no_perm'),
            ];
        }

        if ($boxId == "service_edit") {
            $service = $this->heart->getService($data['id']);
            $service['tag'] = htmlspecialchars($service['tag']);

            // Pobieramy pola danego modułu
            if (strlen($service['module'])) {
                if (
                    ($service_module = $this->heart->getServiceModule($service['id'])) !== null &&
                    $service_module instanceof IServiceAdminManage
                ) {
                    $extra_fields = create_dom_element(
                        "tbody",
                        $service_module->serviceAdminExtraFieldsGet(),
                        [
                            'class' => 'extra_fields',
                        ]
                    );
                }
            }
        }
        // Pobranie dostępnych modułów usług
        elseif ($boxId == "service_add") {
            $services_modules = "";
            foreach ($this->heart->getServicesModules() as $module) {
                // Sprawdzamy czy dany moduł zezwala na tworzenie nowych usług, które będzie obsługiwał
                if (
                    ($service_module = $this->heart->getServiceModuleS($module['id'])) ===
                        null ||
                    !($service_module instanceof IServiceCreate)
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
        foreach ($this->heart->getGroups() as $group) {
            $groups .= create_dom_element("option", "{$group['name']} ( {$group['id']} )", [
                'value' => $group['id'],
                'selected' =>
                    isset($service['groups']) && in_array($group['id'], $service['groups'])
                        ? "selected"
                        : "",
            ]);
        }

        switch ($boxId) {
            case "service_add":
                $output = $this->template->render(
                    "admin/action_boxes/service_add",
                    compact('groups', 'services_modules')
                );
                break;

            case "service_edit":
                $service_module_name = $this->heart->getServiceModuleName($service['module']);

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