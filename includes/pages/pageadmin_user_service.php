<?php

use Admin\Table;

$heart->register_page("user_service", "PageAdmin_UserService", "admin");

class PageAdmin_UserService extends PageAdmin implements IPageAdmin_ActionBox
{

	const PAGE_ID = "user_service";
	protected $privilage = "view_user_services";

	protected function content($get, $post)
	{
		global $heart, $lang;

		$className = '';
		foreach (get_declared_classes() as $class) {
			if (in_array('IService_UserServiceAdminDisplay', class_implements($class)) && $class::MODULE_ID == $get['subpage']) {
				$className = $class;
				break;
			}
		}

		if (!strlen($className))
			return $lang->sprintf($lang->no_subpage, htmlspecialchars($get['subpage']));

		/** @var IService_UserServiceAdminDisplay $service_module_simple */
		$service_module_simple = new $className();

		$this->title = $lang->users_services . ': ' . $service_module_simple->user_service_admin_display_title_get();
		$heart->page_title = $this->title;
		$wrapper = $service_module_simple->user_service_admin_display_get($get, $post);

		if (get_class($wrapper) !== 'Admin\Table\Wrapper')
			return $wrapper;

		$wrapper->setTitle($this->title);

		// Lista z wyborem modułów
		$button = new Table\Select();
		$button->setParam('id', 'user_service_display_module');
		foreach ($heart->get_services_modules() as $service_module_data) {
			if (!in_array('IService_UserServiceAdminDisplay', class_implements($service_module_data['classsimple'])))
				continue;

			$option = new Table\Option($service_module_data['name']);
			$option->setParam('value', $service_module_data['id']);

			if ($service_module_data['id'] == $get['subpage'])
				$option->setParam('selected', 'selected');

			$button->addContent($option);
		}
		$wrapper->addButton($button);

		// Przycisk dodajacy nowa usluge użytkownikowi
		if (get_privilages("manage_user_services")) {
			$button = new Table\Input();
			$button->setParam('id', 'user_service_button_add');
			$button->setParam('type', 'button');
			$button->setParam('value', $lang->add_service);
			$wrapper->addButton($button);
		}

		return $wrapper->toHtml();
	}

	public function get_action_box($box_id, $data)
	{
		global $heart, $db, $lang, $templates;

		if (!get_privilages("manage_user_services"))
			return array(
				'status' => "not_logged_in",
				'text' => $lang->not_logged_or_no_perm
			);

		switch ($box_id) {
			case "user_service_add":
				// Pobranie usług
				$services = "";
				foreach ($heart->get_services() as $id => $row) {
					if (($service_module = $heart->get_service_module($id)) === NULL || !object_implements($service_module, "IService_UserServiceAdminAdd"))
						continue;

					$services .= create_dom_element("option", $row['name'], array(
						'value' => $row['id']
					));
				}

				$output = eval($templates->render("admin/action_boxes/user_service_add"));
				break;

			case "user_service_edit":
				$user_service = get_users_services($data['id']);

				if (empty($user_service) || ($service_module = $heart->get_service_module($user_service['service'])) === NULL
					|| !object_implements($service_module, "IService_UserServiceAdminEdit")
				) {
					$form_data = $lang->service_edit_unable;
				} else {
					$service_module_id = htmlspecialchars($service_module::MODULE_ID);
					$form_data = $service_module->user_service_admin_edit_form_get($user_service);
				}

				$output = eval($templates->render("admin/action_boxes/user_service_edit"));
				break;
		}

		return array(
			'status' => 'ok',
			'template' => $output
		);
	}
}