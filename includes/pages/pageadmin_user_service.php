<?php

$heart->register_page("user_service", "PageAdmin_UserService", "admin");

class PageAdmin_UserService extends PageAdmin implements IPageAdminActionBox
{

	const PAGE_ID = "user_service";
	protected $privilage = "view_user_services";

	protected function content($get, $post)
	{
		global $heart, $lang, $templates;

		$className = '';
		foreach (get_declared_classes() as $class) {
			if (in_array('IPageAdmin_UserService', class_implements($class)) && $class::PAGE_ID == $get['subpage']) {
				$className = $class;
				break;
			}
		}

		if (!strlen($className))
			return "Brak podstrony o ID: " . htmlspecialchars($get['subpage']);

		/** @var IPageAdmin_UserService $subpage */
		$subpage = new $className($get, $post);

		$this->title = $subpage->get_title();
		$content = $subpage->get_content($get, $post);

		if (!is_array($content))
			return $content;

		// Pobranie przycisku dodajacego flagi
		if (get_privilages("manage_user_services"))
			$content['buttons'] .= create_dom_element("input", "", array(
				'id' => "user_service_button_add",
				'type' => "button",
				'value' => $lang->add_service
			));

		extract($content);

		return eval($templates->render("admin/table_structure"));
	}

	public function get_action_box($box_id, $data)
	{
		global $heart, $db, $lang, $templates;

		if (!get_privilages("manage_user_services"))
			return array(
				'id'	=> "not_logged_in",
				'text'	=> $lang->not_logged_or_no_perm
			);

		switch($box_id) {
			case "user_service_add":
				// Pobranie usług
				$services = "";
				foreach ($heart->get_services() as $id => $row) {
					if (($service_module = $heart->get_service_module($id)) === NULL || !object_implements($service_module, "IService_UserServiceAdminManage"))
						continue;

					$services .= create_dom_element("option", $row['name'], array(
						'value' => $row['id']
					));
				}

				$output = eval($templates->render("admin/action_boxes/user_service_add"));
				break;

			case "user_service_edit":
				$user_service = get_users_services($db->prepare(
					"`id` = '%d'",
					array($data['id'])
				));

				if (!empty($user_service) && ($service_module = $heart->get_service_module($user_service['service'])) !== NULL) {
					$service_module_id = htmlspecialchars($service_module::MODULE_ID);
					$form_data = $service_module->user_service_admin_edit_form_get($user_service);
				}

				if (!isset($form_data) || !strlen($form_data))
					$form_data = $lang->service_edit_unable;

				$output = eval($templates->render("admin/action_boxes/user_service_edit"));
				break;
		}

		return array(
			'id'		=> "ok",
			'template'	=> $output
		);
	}
}