<?php
namespace App\View\Pages;

use App\Exceptions\UnauthorizedException;
use App\View\Html\Div;
use App\View\Html\Input;
use App\View\Html\Option;
use App\View\Html\Select;
use App\View\Html\Wrapper;
use App\View\Pages\Interfaces\IPageAdminActionBox;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminAdd;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminDisplay;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminEdit;

class PageAdminUserService extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = 'user_service';
    protected $privilege = 'view_user_services';

    protected function content(array $query, array $body)
    {
        $className = '';
        foreach ($this->heart->getServicesModules() as $module) {
            $class = $module['class'];
            if (
                in_array(IServiceUserServiceAdminDisplay::class, class_implements($class)) &&
                $module['id'] == $query['subpage']
            ) {
                $className = $class;
                break;
            }
        }

        if (!strlen($className)) {
            return $this->lang->t('no_subpage', htmlspecialchars($query['subpage']));
        }

        /** @var IServiceUserServiceAdminDisplay $serviceModule */
        $serviceModule = $this->app->make($className);

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
        if (get_privileges("manage_user_services")) {
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
        if (!get_privileges("manage_user_services")) {
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
                $userService = get_users_services($query['id']);

                if (
                    empty($userService) ||
                    ($serviceModule = $this->heart->getServiceModule($userService['service'])) ===
                        null ||
                    !($serviceModule instanceof IServiceUserServiceAdminEdit)
                ) {
                    $formData = $this->lang->t('service_edit_unable');
                } else {
                    $serviceModuleId = $serviceModule->getModuleId();
                    $formData = $serviceModule->userServiceAdminEditFormGet($userService);
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
        $button = new Select();
        $button->setParam('id', 'user_service_display_module');
        $button->addClass("select is-small");

        $selectWrapper = new Div();
        $selectWrapper->addClass("select is-small");
        $selectWrapper->addContent($button);

        foreach ($this->heart->getServicesModules() as $serviceModuleData) {
            if (
                !in_array(
                    IServiceUserServiceAdminDisplay::class,
                    class_implements($serviceModuleData['class'])
                )
            ) {
                continue;
            }

            $option = new Option($serviceModuleData['name']);
            $option->setParam('value', $serviceModuleData['id']);

            if ($serviceModuleData['id'] == $subpage) {
                $option->setParam('selected', 'selected');
            }

            $button->addContent($option);
        }

        return $selectWrapper;
    }
}
