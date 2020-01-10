<?php
namespace App\View\Pages;

use App\Exceptions\UnauthorizedException;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\HeadCell;
use App\View\Html\Input;
use App\View\Html\Structure;
use App\View\Html\UnescapedSimpleText;
use App\View\Html\Wrapper;
use App\View\Pages\Interfaces\IPageAdminActionBox;
use App\ServiceModules\Interfaces\IServiceAdminManage;
use App\ServiceModules\Interfaces\IServiceCreate;

class PageAdminServices extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = 'services';
    protected $privilege = 'view_services';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t('services');
    }

    protected function content(array $query, array $body)
    {
        $wrapper = new Wrapper();
        $wrapper->setTitle($this->title);

        $table = new Structure();
        $table->addHeadCell(new HeadCell($this->lang->t('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->t('name')));
        $table->addHeadCell(new HeadCell($this->lang->t('short_description')));
        $table->addHeadCell(new HeadCell($this->lang->t('description')));
        $table->addHeadCell(new HeadCell($this->lang->t('order')));

        foreach ($this->heart->getServices() as $service) {
            $bodyRow = new BodyRow();

            $bodyRow->setDbId($service->getId());

            $nameCell = new Cell($service->getName());
            $nameCell->setParam('headers', 'name');
            $bodyRow->addCell($nameCell);
            $bodyRow->addCell(new Cell($service->getShortDescription()));
            $bodyRow->addCell(new Cell($service->getDescription()));
            $bodyRow->addCell(new Cell($service->getOrder()));

            if (get_privileges('manage_services')) {
                $bodyRow->setDeleteAction(true);
                $bodyRow->setEditAction(true);
            }

            $table->addBodyRow($bodyRow);
        }

        $wrapper->setTable($table);

        if (get_privileges('manage_services')) {
            $button = new Input();
            $button->setParam('id', 'service_button_add');
            $button->setParam('type', 'button');
            $button->addClass('button');
            $button->setParam('value', $this->lang->t('add_service'));
            $wrapper->addButton($button);
        }

        return $wrapper;
    }

    public function getActionBox($boxId, array $query)
    {
        if (!get_privileges("manage_services")) {
            throw new UnauthorizedException();
        }

        if ($boxId == "service_edit") {
            $service = $this->heart->getService($query['id']);

            // Pobieramy pola danego modułu
            if (strlen($service->getModule())) {
                $serviceModule = $this->heart->getServiceModule($service->getId());

                if ($serviceModule instanceof IServiceAdminManage) {
                    $extraFields = create_dom_element(
                        "tbody",
                        new UnescapedSimpleText($serviceModule->serviceAdminExtraFieldsGet()),
                        [
                            'class' => 'extra_fields',
                        ]
                    );
                }
            }
        }
        // Pobranie dostępnych modułów usług
        elseif ($boxId == "service_add") {
            $servicesModules = "";
            foreach ($this->heart->getServicesModules() as $module) {
                // Sprawdzamy czy dany moduł zezwala na tworzenie nowych usług, które będzie obsługiwał
                $serviceModule = $this->heart->getEmptyServiceModule($module['id']);
                if (!($serviceModule instanceof IServiceCreate)) {
                    continue;
                }

                $servicesModules .= create_dom_element("option", $module['name'], [
                    'value' => $module['id'],
                ]);
            }
        }

        // Grupy
        $groups = "";
        foreach ($this->heart->getGroups() as $group) {
            $groups .= create_dom_element("option", "{$group['name']} ( {$group['id']} )", [
                'value' => $group['id'],
                'selected' =>
                    isset($service) && in_array($group['id'], $service->getGroups())
                        ? "selected"
                        : "",
            ]);
        }

        switch ($boxId) {
            case "service_add":
                $output = $this->template->render(
                    "admin/action_boxes/service_add",
                    compact('groups', 'servicesModules')
                );
                break;

            case "service_edit":
                $serviceModuleName = $this->heart->getServiceModuleName($service->getModule());

                $output = $this->template->render(
                    "admin/action_boxes/service_edit",
                    compact('service', 'groups', 'serviceModuleName', 'extraFields')
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
