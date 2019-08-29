<?php
namespace App\Pages;

use Admin\Table;
use Admin\Table\Wrapper;
use App\Pages\Interfaces\IPageAdminActionBox;
use App\Services\Interfaces\IServiceUserServiceAdminAdd;
use App\Services\Interfaces\IServiceUserServiceAdminDisplay;
use App\Services\Interfaces\IServiceUserServiceAdminEdit;

class PageAdminUserService extends PageAdmin implements IPageAdminActionBox
{
    const PAGE_ID = 'user_service';
    protected $privilege = 'view_user_services';

    protected function content($get, $post)
    {
        $className = '';
        foreach ($this->heart->getServicesModules() as $module) {
            $class = $module['classsimple'];
            if (
                in_array(IServiceUserServiceAdminDisplay::class, class_implements($class)) &&
                $module['id'] == $get['subpage']
            ) {
                $className = $class;
                break;
            }
        }

        if (!strlen($className)) {
            return $this->lang->sprintf(
                $this->lang->translate('no_subpage'),
                htmlspecialchars($get['subpage'])
            );
        }

        /** @var IServiceUserServiceAdminDisplay $serviceModuleSimple */
        $serviceModuleSimple = $this->app->make($className);

        $this->title =
            $this->lang->translate('users_services') .
            ': ' .
            $serviceModuleSimple->userServiceAdminDisplayTitleGet();
        $this->heart->pageTitle = $this->title;
        $wrapper = $serviceModuleSimple->userServiceAdminDisplayGet($get, $post);

        if (get_class($wrapper) !== Wrapper::class) {
            return $wrapper;
        }

        $wrapper->setTitle($this->title);

        // Lista z wyborem modułów
        $button = new Table\Select();
        $button->setParam('id', 'user_service_display_module');
        foreach ($this->heart->getServicesModules() as $serviceModuleData) {
            if (
                !in_array(
                    IServiceUserServiceAdminDisplay::class,
                    class_implements($serviceModuleData['class'])
                )
            ) {
                continue;
            }

            $option = new Table\Option($serviceModuleData['name']);
            $option->setParam('value', $serviceModuleData['id']);

            if ($serviceModuleData['id'] == $get['subpage']) {
                $option->setParam('selected', 'selected');
            }

            $button->addContent($option);
        }
        $wrapper->addButton($button);

        // Przycisk dodajacy nowa usluge użytkownikowi
        if (get_privileges("manage_user_services")) {
            $button = new Table\Input();
            $button->setParam('id', 'user_service_button_add');
            $button->setParam('type', 'button');
            $button->setParam('class', 'button');
            $button->setParam('value', $this->lang->translate('add_service'));
            $wrapper->addButton($button);
        }

        return $wrapper->toHtml();
    }

    public function getActionBox($boxId, $data)
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
                $user_service = get_users_services($data['id']);

                if (
                    empty($user_service) ||
                    ($serviceModule = $this->heart->getServiceModule(
                        $user_service['service']
                    )) === null ||
                    !($serviceModule instanceof IServiceUserServiceAdminEdit)
                ) {
                    $form_data = $this->lang->translate('service_edit_unable');
                } else {
                    $service_module_id = htmlspecialchars($serviceModule->getModuleId());
                    $form_data = $serviceModule->userServiceAdminEditFormGet($user_service);
                }

                $output = $this->template->render(
                    "admin/action_boxes/user_service_edit",
                    compact('service_module_id', 'form_data')
                );
                break;
        }

        return [
            'status' => isset($output) ? 'ok' : 'no_output',
            'template' => if_isset($output, ''),
        ];
    }
}
