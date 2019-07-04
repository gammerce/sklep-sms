<?php

use Admin\Table;

class PageAdmin_UserService extends PageAdmin implements IPageAdmin_ActionBox
{
    const PAGE_ID = 'user_service';
    protected $privilage = 'view_user_services';

    protected function content($get, $post)
    {
        $className = '';
        foreach (get_declared_classes() as $class) {
            if (
                in_array('IService_UserServiceAdminDisplay', class_implements($class)) &&
                $class::MODULE_ID == $get['subpage']
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

        /** @var IService_UserServiceAdminDisplay $service_module_simple */
        $service_module_simple = $this->app->make($className);

        $this->title =
            $this->lang->translate('users_services') .
            ': ' .
            $service_module_simple->user_service_admin_display_title_get();
        $this->heart->page_title = $this->title;
        $wrapper = $service_module_simple->user_service_admin_display_get($get, $post);

        if (get_class($wrapper) !== 'Admin\Table\Wrapper') {
            return $wrapper;
        }

        $wrapper->setTitle($this->title);

        // Lista z wyborem modułów
        $button = new Table\Select();
        $button->setParam('id', 'user_service_display_module');
        foreach ($this->heart->get_services_modules() as $service_module_data) {
            if (
                !in_array(
                    'IService_UserServiceAdminDisplay',
                    class_implements($service_module_data['classsimple'])
                )
            ) {
                continue;
            }

            $option = new Table\Option($service_module_data['name']);
            $option->setParam('value', $service_module_data['id']);

            if ($service_module_data['id'] == $get['subpage']) {
                $option->setParam('selected', 'selected');
            }

            $button->addContent($option);
        }
        $wrapper->addButton($button);

        // Przycisk dodajacy nowa usluge użytkownikowi
        if (get_privilages("manage_user_services")) {
            $button = new Table\Input();
            $button->setParam('id', 'user_service_button_add');
            $button->setParam('type', 'button');
            $button->setParam('value', $this->lang->translate('add_service'));
            $wrapper->addButton($button);
        }

        return $wrapper->toHtml();
    }

    public function get_action_box($box_id, $data)
    {
        if (!get_privilages("manage_user_services")) {
            return [
                'status' => "not_logged_in",
                'text' => $this->lang->translate('not_logged_or_no_perm'),
            ];
        }

        switch ($box_id) {
            case "user_service_add":
                // Pobranie usług
                $services = "";
                foreach ($this->heart->get_services() as $id => $row) {
                    if (
                        ($service_module = $this->heart->get_service_module($id)) === null ||
                        !($service_module instanceof IService_UserServiceAdminAdd)
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
                    ($service_module = $this->heart->get_service_module(
                        $user_service['service']
                    )) === null ||
                    !($service_module instanceof IService_UserServiceAdminEdit)
                ) {
                    $form_data = $this->lang->translate('service_edit_unable');
                } else {
                    $service_module_id = htmlspecialchars($service_module::MODULE_ID);
                    $form_data = $service_module->user_service_admin_edit_form_get($user_service);
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
