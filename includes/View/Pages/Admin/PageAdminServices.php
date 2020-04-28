<?php
namespace App\View\Pages\Admin;

use App\Exceptions\UnauthorizedException;
use App\Models\Group;
use App\Models\Service;
use App\ServiceModules\Interfaces\IServiceAdminManage;
use App\ServiceModules\Interfaces\IServiceCreate;
use App\ServiceModules\ServiceModule;
use App\Support\Template;
use App\System\Heart;
use App\Translation\TranslationManager;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\HeadCell;
use App\View\Html\Input;
use App\View\Html\RawText;
use App\View\Html\Structure;
use App\View\Html\Wrapper;
use App\View\Pages\IPageAdminActionBox;
use Symfony\Component\HttpFoundation\Request;

class PageAdminServices extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = "services";

    /** @var Heart */
    private $heart;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        Heart $heart
    ) {
        parent::__construct($template, $translationManager);
        $this->heart = $heart;
    }

    public function getPrivilege()
    {
        return "view_services";
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("services");
    }

    public function getContent(Request $request)
    {
        $recordId = $request->query->get("record");

        $bodyRows = collect($this->heart->getServices())
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
                    ->setDeleteAction(has_privileges("manage_services"))
                    ->setEditAction(has_privileges("manage_services"))
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

        if (has_privileges("manage_services")) {
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
        if (!has_privileges("manage_services")) {
            throw new UnauthorizedException();
        }

        if ($boxId == "service_edit") {
            $service = $this->heart->getService($query["id"]);

            if (strlen($service->getModule())) {
                $serviceModule = $this->heart->getServiceModule($service->getId());

                if ($serviceModule instanceof IServiceAdminManage) {
                    $extraFields = create_dom_element(
                        "tbody",
                        new RawText($serviceModule->serviceAdminExtraFieldsGet()),
                        [
                            "class" => "extra_fields",
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
                            "value" => $serviceModule->getModuleId(),
                        ]
                    );
                })
                ->join();
        }

        $groups = collect($this->heart->getGroups())
            ->map(function (Group $group) {
                return create_dom_element("option", "{$group->getName()} ( {$group->getId()} )", [
                    "value" => $group->getId(),
                    "selected" =>
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
                    compact("groups", "servicesModules")
                );
                break;

            case "service_edit":
                $serviceModuleName = $this->heart->getServiceModuleName($service->getModule());

                $output = $this->template->render(
                    "admin/action_boxes/service_edit",
                    compact("service", "groups", "serviceModuleName", "extraFields")
                );
                break;

            default:
                $output = "";
        }

        return [
            "status" => "ok",
            "template" => $output,
        ];
    }
}
