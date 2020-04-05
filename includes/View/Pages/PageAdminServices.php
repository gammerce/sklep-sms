<?php
namespace App\View\Pages;

use App\Exceptions\UnauthorizedException;
use App\Models\Group;
use App\Models\Service;
use App\ServiceModules\Interfaces\IServiceAdminManage;
use App\ServiceModules\Interfaces\IServiceCreate;
use App\ServiceModules\ServiceModule;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\HeadCell;
use App\View\Html\Input;
use App\View\Html\Structure;
use App\View\Html\UnescapedSimpleText;
use App\View\Html\Wrapper;
use App\View\Pages\Interfaces\IPageAdminActionBox;

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
        $bodyRows = collect($this->heart->getServices())
            ->map(function (Service $service) {
                return (new BodyRow())
                    ->setDbId($service->getId())
                    ->addCell(new Cell($service->getName(), 'name'))
                    ->addCell(new Cell($service->getShortDescription()))
                    ->addCell(new Cell($service->getDescription()))
                    ->addCell(new Cell($service->getOrder()))
                    ->setDeleteAction(has_privileges('manage_services'))
                    ->setEditAction(has_privileges('manage_services'));
            })
            ->all();

        $table = (new Structure())
            ->addHeadCell(new HeadCell($this->lang->t('id'), "id"))
            ->addHeadCell(new HeadCell($this->lang->t('name')))
            ->addHeadCell(new HeadCell($this->lang->t('short_description')))
            ->addHeadCell(new HeadCell($this->lang->t('description')))
            ->addHeadCell(new HeadCell($this->lang->t('order')))
            ->addBodyRows($bodyRows);

        $wrapper = (new Wrapper())->setTitle($this->title)->setTable($table);

        if (has_privileges('manage_services')) {
            $button = (new Input())
                ->setParam('id', 'service_button_add')
                ->setParam('type', 'button')
                ->addClass('button')
                ->setParam('value', $this->lang->t('add_service'));

            $wrapper->addButton($button);
        }

        return $wrapper;
    }

    public function getActionBox($boxId, array $query)
    {
        if (!has_privileges("manage_services")) {
            throw new UnauthorizedException();
        }

        if ($boxId == "service_edit") {
            $service = $this->heart->getService($query['id']);

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
        } elseif ($boxId == "service_add") {
            $servicesModules = collect($this->heart->getEmptyServiceModules())
                ->filter(function (ServiceModule $serviceModule) {
                    return $serviceModule instanceof IServiceCreate;
                })
                ->map(function (ServiceModule $serviceModule) {
                    return create_dom_element(
                        "option",
                        $this->heart->getServiceModuleName($serviceModule->getModuleId()),
                        [
                            'value' => $serviceModule->getModuleId(),
                        ]
                    );
                })
                ->join();
        }

        $groups = collect($this->heart->getGroups())
            ->map(function (Group $group) {
                return create_dom_element("option", "{$group->getName()} ( {$group->getId()} )", [
                    'value' => $group->getId(),
                    'selected' =>
                        isset($service) && in_array($group->getId(), $service->getGroups())
                            ? "selected"
                            : "",
                ]);
            })
            ->join();

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
