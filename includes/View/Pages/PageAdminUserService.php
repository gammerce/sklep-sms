<?php
namespace App\View\Pages;

use App\Exceptions\UnauthorizedException;
use App\Models\Service;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminAdd;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminDisplay;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminEdit;
use App\ServiceModules\ServiceModule;
use App\Services\UserServiceService;
use App\Support\Template;
use App\System\Heart;
use App\Translation\TranslationManager;
use App\View\Html\Div;
use App\View\Html\Input;
use App\View\Html\Option;
use App\View\Html\Select;
use App\View\Html\Wrapper;
use App\View\Pages\Interfaces\IPageAdminActionBox;
use Symfony\Component\HttpFoundation\Request;

class PageAdminUserService extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = "user_service";

    /** @var UserServiceService */
    private $userServiceService;

    /** @var Heart */
    private $heart;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        UserServiceService $userServiceService,
        Heart $heart
    ) {
        parent::__construct($template, $translationManager);
        $this->userServiceService = $userServiceService;
        $this->heart = $heart;
    }

    public function getPrivilege()
    {
        return "view_user_services";
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

        // Przycisk dodajacy nowa usluge użytkownikowi
        if (has_privileges("manage_user_services")) {
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
        if (!has_privileges("manage_user_services")) {
            throw new UnauthorizedException();
        }

        switch ($boxId) {
            case "user_service_add":
                $services = collect($this->heart->getServices())
                    ->filter(function (Service $service) {
                        $serviceModule = $this->heart->getServiceModule($service->getId());
                        return $serviceModule instanceof IServiceUserServiceAdminAdd;
                    })
                    ->map(function (Service $service) {
                        return create_dom_element("option", $service->getName(), [
                            "value" => $service->getId(),
                        ]);
                    })
                    ->join();

                $output = $this->template->render(
                    "admin/action_boxes/user_service_add",
                    compact("services")
                );
                break;

            case "user_service_edit":
                $userService = $this->userServiceService->findOne($query["id"]);

                $formData = $this->lang->t("service_edit_unable");

                if ($userService) {
                    $serviceModule = $this->heart->getServiceModule($userService->getServiceId());

                    if ($serviceModule instanceof IServiceUserServiceAdminEdit) {
                        $serviceModuleId = $serviceModule->getModuleId();
                        $formData = $serviceModule->userServiceAdminEditFormGet($userService);
                    }
                }

                $output = $this->template->render(
                    "admin/action_boxes/user_service_edit",
                    compact("serviceModuleId", "formData")
                );
                break;
        }

        return [
            "status" => isset($output) ? "ok" : "no_output",
            "template" => isset($output) ? $output : "",
        ];
    }

    private function createModuleSelectBox($subpage)
    {
        $button = (new Select())
            ->setParam("id", "user_service_display_module")
            ->addClass("select is-small");

        $selectWrapper = (new Div($button))->addClass("select is-small");

        foreach ($this->heart->getEmptyServiceModules() as $serviceModule) {
            if (!($serviceModule instanceof IServiceUserServiceAdminDisplay)) {
                continue;
            }

            $option = new Option(
                $this->heart->getServiceModuleName($serviceModule->getModuleId()),
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
        $serviceModule = $this->heart->getEmptyServiceModule($subPage);
        return $serviceModule instanceof IServiceUserServiceAdminDisplay ? $serviceModule : null;
    }
}
