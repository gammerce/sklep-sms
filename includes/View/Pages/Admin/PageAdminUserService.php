<?php
namespace App\View\Pages\Admin;

use App\Exceptions\EntityNotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Managers\ServiceManager;
use App\Managers\ServiceModuleManager;
use App\Models\Service;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminAdd;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminDisplay;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminEdit;
use App\ServiceModules\ServiceModule;
use App\Services\UserServiceService;
use App\Support\Template;
use App\Translation\TranslationManager;
use App\User\Permission;
use App\View\Html\Div;
use App\View\Html\Input;
use App\View\Html\Option;
use App\View\Html\Select;
use App\View\Html\Wrapper;
use App\View\Pages\IPageAdminActionBox;
use Symfony\Component\HttpFoundation\Request;

class PageAdminUserService extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = "user_service";

    /** @var UserServiceService */
    private $userServiceService;

    /** @var ServiceManager */
    private $serviceManager;

    /** @var ServiceModuleManager */
    private $serviceModuleManager;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        UserServiceService $userServiceService,
        ServiceModuleManager $serviceModuleManager,
        ServiceManager $serviceManager
    ) {
        parent::__construct($template, $translationManager);
        $this->userServiceService = $userServiceService;
        $this->serviceManager = $serviceManager;
        $this->serviceModuleManager = $serviceModuleManager;
    }

    public function getPrivilege()
    {
        return Permission::VIEW_USER_SERVICES();
    }

    public function getTitle(Request $request)
    {
        $serviceModule = $this->getServiceModule($request);
        $title = $this->lang->t("users_services");

        if ($serviceModule) {
            $title .= ": {$serviceModule->userServiceAdminDisplayTitleGet()}";
        }

        return $title;
    }

    public function getContent(Request $request)
    {
        $subPage = $request->query->get("subpage");
        $serviceModule = $this->getServiceModule($request);

        if (!$serviceModule) {
            return $this->lang->t("no_subpage");
        }

        $wrapper = $serviceModule->userServiceAdminDisplayGet(
            $request->query->all(),
            $request->request->all()
        );

        if (get_class($wrapper) !== Wrapper::class) {
            return $wrapper;
        }

        $wrapper->setTitle($this->getTitle($request));
        $wrapper->addButton($this->createModuleSelectBox($subPage));

        if (can(Permission::MANAGE_USER_SERVICES())) {
            $button = (new Input())
                ->setParam("id", "user_service_button_add")
                ->setParam("type", "button")
                ->addClass("button is-small")
                ->setParam("value", $this->lang->t("add_service"));
            $wrapper->addButton($button);
        }

        return $wrapper;
    }

    public function getActionBox($boxId, array $query)
    {
        if (cannot(Permission::MANAGE_USER_SERVICES())) {
            throw new UnauthorizedException();
        }

        switch ($boxId) {
            case "add":
                $services = collect($this->serviceManager->getServices())
                    ->filter(function (Service $service) {
                        $serviceModule = $this->serviceModuleManager->get($service->getId());
                        return $serviceModule instanceof IServiceUserServiceAdminAdd;
                    })
                    ->map(function (Service $service) {
                        return new Option($service->getName(), $service->getId());
                    })
                    ->join();

                return $this->template->render(
                    "admin/action_boxes/user_service_add",
                    compact("services")
                );

            case "edit":
                $userService = $this->userServiceService->findOne($query["id"]);

                $serviceModuleId = 0;
                $formData = $this->lang->t("service_edit_unable");

                if ($userService) {
                    $serviceModule = $this->serviceModuleManager->get($userService->getServiceId());

                    if ($serviceModule instanceof IServiceUserServiceAdminEdit) {
                        $serviceModuleId = $serviceModule->getModuleId();
                        $formData = $serviceModule->userServiceAdminEditFormGet($userService);
                    }
                }

                return $this->template->render(
                    "admin/action_boxes/user_service_edit",
                    compact("serviceModuleId", "formData")
                );

            default:
                throw new EntityNotFoundException();
        }
    }

    private function createModuleSelectBox($subpage)
    {
        $button = (new Select())
            ->setParam("id", "user_service_display_module")
            ->addClass("select is-small");

        $selectWrapper = (new Div($button))->addClass("select is-small");

        foreach ($this->serviceModuleManager->all() as $serviceModule) {
            if (!($serviceModule instanceof IServiceUserServiceAdminDisplay)) {
                continue;
            }

            $option = new Option(
                $this->serviceModuleManager->getName($serviceModule->getModuleId()),
                $serviceModule->getModuleId()
            );

            if ($serviceModule->getModuleId() == $subpage) {
                $option->setParam("selected", "selected");
            }

            $button->addContent($option);
        }

        return $selectWrapper;
    }

    /**
     * @param Request $request
     * @return IServiceUserServiceAdminDisplay|ServiceModule
     */
    private function getServiceModule(Request $request)
    {
        $subPage = $request->query->get("subpage");
        $serviceModule = $this->serviceModuleManager->getEmpty($subPage);
        return $serviceModule instanceof IServiceUserServiceAdminDisplay ? $serviceModule : null;
    }
}
