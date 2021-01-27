<?php
namespace App\View\Pages\Admin;

use App\Exceptions\EntityNotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Managers\GroupManager;
use App\Managers\ServerManager;
use App\Managers\ServerServiceManager;
use App\Managers\ServiceManager;
use App\Managers\ServiceModuleManager;
use App\Models\Group;
use App\Models\Server;
use App\Models\Service;
use App\ServiceModules\Interfaces\IServiceAdminManage;
use App\ServiceModules\Interfaces\IServiceCreate;
use App\ServiceModules\ServiceModule;
use App\Support\Template;
use App\Translation\TranslationManager;
use App\User\Permission;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\HeadCell;
use App\View\Html\Input;
use App\View\Html\Option;
use App\View\Html\RawHtml;
use App\View\Html\Structure;
use App\View\Html\Wrapper;
use App\View\Pages\IPageAdminActionBox;
use Symfony\Component\HttpFoundation\Request;

class PageAdminServices extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = "services";

    /** @var ServiceModuleManager */
    private $serviceModuleManager;

    /** @var GroupManager */
    private $groupManager;

    /** @var ServiceManager */
    private $serviceManager;

    /** @var ServerManager */
    private $serverManager;

    /** @var ServerServiceManager */
    private $serverServiceManager;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        ServiceModuleManager $serviceModuleManager,
        GroupManager $groupManager,
        ServiceManager $serviceManager,
        ServerManager $serverManager,
        ServerServiceManager $serverServiceManager
    ) {
        parent::__construct($template, $translationManager);
        $this->serviceModuleManager = $serviceModuleManager;
        $this->groupManager = $groupManager;
        $this->serviceManager = $serviceManager;
        $this->serverManager = $serverManager;
        $this->serverServiceManager = $serverServiceManager;
    }

    public function getPrivilege()
    {
        return Permission::VIEW_SERVICES();
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("services");
    }

    public function getContent(Request $request)
    {
        $recordId = $request->query->get("record");

        $bodyRows = collect($this->serviceManager->all())
            ->filter(function (Service $service) use ($recordId) {
                return $recordId === null || $service->getId() === $recordId;
            })
            ->map(function (Service $service) use ($recordId) {
                return (new BodyRow())
                    ->setDbId($service->getId())
                    ->addCell(new Cell($service->getName(), "name"))
                    ->addCell(new Cell($service->getShortDescription()))
                    ->addCell(new Cell($service->getDescription()))
                    ->addCell(new Cell($service->getOrder()))
                    ->setDeleteAction(can(Permission::MANAGE_SERVICES()))
                    ->setEditAction(can(Permission::MANAGE_SERVICES()))
                    ->when($recordId === $service->getId(), function (BodyRow $bodyRow) {
                        $bodyRow->addClass("highlighted");
                    });
            })
            ->all();

        $table = (new Structure())
            ->addHeadCell(new HeadCell($this->lang->t("id"), "id"))
            ->addHeadCell(new HeadCell($this->lang->t("name")))
            ->addHeadCell(new HeadCell($this->lang->t("short_description")))
            ->addHeadCell(new HeadCell($this->lang->t("description")))
            ->addHeadCell(new HeadCell($this->lang->t("order")))
            ->addBodyRows($bodyRows);

        $wrapper = (new Wrapper())->setTitle($this->getTitle($request))->setTable($table);

        if (can(Permission::MANAGE_SERVICES())) {
            $button = (new Input())
                ->setParam("id", "service_button_add")
                ->setParam("type", "button")
                ->addClass("button")
                ->setParam("value", $this->lang->t("add_service"));

            $wrapper->addButton($button);
        }

        return $wrapper;
    }

    public function getActionBox($boxId, array $query)
    {
        if (cannot(Permission::MANAGE_SERVICES())) {
            throw new UnauthorizedException();
        }

        if ($boxId === "edit") {
            $service = $this->serviceManager->get($query["id"]);
        } else {
            $service = null;
        }

        $groups = collect($this->groupManager->all())
            ->map(function (Group $group) use ($service) {
                $selected = $service && in_array($group->getId(), $service->getGroups());
                return new Option("{$group->getName()} ({$group->getId()})", $group->getId(), [
                    "selected" => selected($selected),
                ]);
            })
            ->join();

        $servers = collect($this->serverManager->all())
            ->map(function (Server $server) use ($service) {
                $isLinked =
                    $service &&
                    $this->serverServiceManager->serverServiceLinked(
                        $server->getId(),
                        $service->getId()
                    );

                return new Option("{$server->getName()} ({$server->getId()})", $server->getId(), [
                    "selected" => selected($isLinked),
                ]);
            })
            ->join();

        switch ($boxId) {
            case "add":
                $servicesModules = collect($this->serviceModuleManager->all())
                    ->filter(function (ServiceModule $serviceModule) {
                        return $serviceModule instanceof IServiceCreate;
                    })
                    ->map(function (ServiceModule $serviceModule) {
                        return new Option(
                            $this->serviceModuleManager->getName($serviceModule->getModuleId()),
                            $serviceModule->getModuleId()
                        );
                    })
                    ->join();

                return $this->template->render(
                    "admin/action_boxes/service_add",
                    compact("groups", "servers", "servicesModules")
                );

            case "edit":
                if (strlen($service->getModule())) {
                    $serviceModule = $this->serviceModuleManager->get($service->getId());

                    if ($serviceModule instanceof IServiceAdminManage) {
                        $extraFields = create_dom_element(
                            "tbody",
                            new RawHtml($serviceModule->serviceAdminExtraFieldsGet()),
                            [
                                "class" => "extra_fields",
                            ]
                        );
                    }
                }

                $serviceModuleName = $this->serviceModuleManager->getName($service->getModule());

                return $this->template->render(
                    "admin/action_boxes/service_edit",
                    compact("service", "groups", "servers", "serviceModuleName", "extraFields")
                );

            default:
                throw new EntityNotFoundException();
        }
    }
}
