<?php
namespace App\Pages;

use App\Html\Div;
use App\Html\Input;
use App\Html\Option;
use App\Html\Select;
use App\Html\Wrapper;
use App\Pages\Interfaces\IPageAdminActionBox;
use App\Services\Interfaces\IServiceUserServiceAdminAdd;
use App\Services\Interfaces\IServiceUserServiceAdminDisplay;
use App\Services\Interfaces\IServiceUserServiceAdminEdit;

class PageAdminUserService extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = 'user_service';
    protected $privilege = 'view_user_services';

    protected function content(array $query, array $body)
    {
        $className = '';
        foreach ($this->heart->getServicesModules() as $module) {
            $class = $module['classsimple'];
            if (
                in_array(IServiceUserServiceAdminDisplay::class, class_implements($class)) &&
                $module['id'] == $query['subpage']
            ) {
                $className = $class;
                break;
            }
        }

        if (!strlen($className)) {
            return $this->lang->sprintf(
                $this->lang->translate('no_subpage'),
                htmlspecialchars($query['subpage'])
            );
        }

        /** @var IServiceUserServiceAdminDisplay $serviceModuleSimple */
        $serviceModuleSimple = $this->app->make($className);

        $this->title =
            $this->lang->translate('users_services') .
            ': ' .
            $serviceModuleSimple->userServiceAdminDisplayTitleGet();
        $this->heart->pageTitle = $this->title;
        $wrapper = $serviceModuleSimple->userServiceAdminDisplayGet($query, $body);

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
            $button->setParam('value', $this->lang->translate('add_service'));
            $wrapper->addButton($button);
        }

        return $wrapper->toHtml();
    }

    public function getActionBox($boxId, array $query)
    {
        if (!get_privileges("manage_user_services")) {
            return [
                'status' => "not_logged_in",
                'text' => $this->lang->translate('not_logged_or_no_perm'),
            ];
        }

        switch ($boxId) {
            case "user_service_add":
                // Pobranie usług
                $services = "";
                foreach ($this->heart->getServices() as $id => $row) {
                    if (
                        ($serviceModule = $this->heart->getServiceModule($id)) === null ||
                        !($serviceModule instanceof IServiceUserServiceAdminAdd)
                    ) {
                        continue;
                    }

                    $services .= create_dom_element("option", $row['name'], [
                        'value' => $row['id'],
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
                    $formData = $this->lang->translate('service_edit_unable');
                } else {
                    $serviceModuleId = htmlspecialchars($serviceModule->getModuleId());
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
            'template' => if_isset($output, ''),
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
