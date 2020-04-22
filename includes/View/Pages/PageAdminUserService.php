<?php
namespace App\View\Pages;

use App\Exceptions\UnauthorizedException;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminAdd;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminDisplay;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminEdit;
use App\Services\UserServiceService;
use App\View\Html\Div;
use App\View\Html\Input;
use App\View\Html\Option;
use App\View\Html\Select;
use App\View\Html\Wrapper;
use App\View\Pages\Interfaces\IPageAdminActionBox;

class PageAdminUserService extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = 'user_service';
    protected $privilege = 'view_user_services';

    /** @var UserServiceService */
    private $userServiceService;

    public function __construct(UserServiceService $userServiceService)
    {
        parent::__construct();
        $this->userServiceService = $userServiceService;
    }

    protected function content(array $query, array $body)
    {
        $serviceModule = null;
        foreach ($this->heart->getEmptyServiceModules() as $item) {
            if (
                $item instanceof IServiceUserServiceAdminDisplay &&
                $item->getModuleId() == $query['subpage']
            ) {
                $serviceModule = $item;
                break;
            }
        }

        if (!$serviceModule) {
            return $this->lang->t('no_subpage', htmlspecialchars($query['subpage']));
        }

        $this->title =
            $this->lang->t('users_services') .
            ': ' .
            $serviceModule->userServiceAdminDisplayTitleGet();
        $this->heart->pageTitle = $this->title;
        $wrapper = $serviceModule->userServiceAdminDisplayGet($query, $body);

        if (get_class($wrapper) !== Wrapper::class) {
            return $wrapper;
        }

        $wrapper->setTitle($this->title);
        $wrapper->addButton($this->createModuleSelectBox($query['subpage']));

        // Przycisk dodajacy nowa usluge użytkownikowi
        if (has_privileges("manage_user_services")) {
            $button = new Input();
            $button->setParam('id', 'user_service_button_add');
            $button->setParam('type', 'button');
            $button->addClass('button is-small');
            $button->setParam('value', $this->lang->t('add_service'));
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
                // Pobranie usług
                $services = "";
                foreach ($this->heart->getServices() as $id => $service) {
                    if (
                        ($serviceModule = $this->heart->getServiceModule($id)) === null ||
                        !($serviceModule instanceof IServiceUserServiceAdminAdd)
                    ) {
                        continue;
                    }

                    $services .= create_dom_element("option", $service->getName(), [
                        'value' => $service->getId(),
                    ]);
                }

                $output = $this->template->render(
                    "admin/action_boxes/user_service_add",
                    compact('services')
                );
                break;

            case "user_service_edit":
                $userService = $this->userServiceService->findOne($query['id']);

                $formData = $this->lang->t('service_edit_unable');

                if ($userService) {
                    $serviceModule = $this->heart->getServiceModule($userService->getServiceId());

                    if ($serviceModule instanceof IServiceUserServiceAdminEdit) {
                        $serviceModuleId = $serviceModule->getModuleId();
                        $formData = $serviceModule->userServiceAdminEditFormGet($userService);
                    }
                }

                $output = $this->template->render(
                    "admin/action_boxes/user_service_edit",
                    compact('serviceModuleId', 'formData')
                );
                break;
        }

        return [
            'status' => isset($output) ? 'ok' : 'no_output',
            'template' => isset($output) ? $output : '',
        ];
    }

    protected function createModuleSelectBox($subpage)
    {
        $button = (new Select())
            ->setParam('id', 'user_service_display_module')
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
                $option->setParam('selected', 'selected');
            }

            $button->addContent($option);
        }

        return $selectWrapper;
    }
}
