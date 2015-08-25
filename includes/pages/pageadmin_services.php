<?php

$heart->register_page("services", "PageAdminServices", "admin");

class PageAdminServices extends PageAdmin implements IPageAdmin_ActionBox
{

	const PAGE_ID = "services";
	protected $privilage = "view_services";

	function __construct()
	{
		global $lang;
		$this->title = $lang->services;

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $heart, $lang, $templates;

		// Pobranie listy serwisów transakcyjnych
		$i = 0;
		$tbody = "";
		foreach ($heart->get_services() as $row) {
			$i += 1;
			$row['id'] = htmlspecialchars($row['id']);
			$row['name'] = htmlspecialchars($row['name']);
			$row['short_description'] = htmlspecialchars($row['short_description']);
			$row['description'] = htmlspecialchars($row['description']);

			if (get_privilages("manage_services")) {
				// Pobranie przycisku edycji
				$button_edit = create_dom_element("img", "", array(
					'id' => "edit_row_{$i}",
					'src' => "images/edit.png",
					'title' => $lang->edit . " " . $row['name']
				));
				$button_delete = create_dom_element("img", "", array(
					'id' => "delete_row_{$i}",
					'src' => "images/bin.png",
					'title' => $lang->delete . " " . $row['name']
				));
			} else
				$button_delete = $button_edit = "";

			// Pobranie danych do tabeli
			$tbody .= eval($templates->render("admin/services_trow"));
		}

		// Nie ma zadnych danych do wyswietlenia
		if (!strlen($tbody))
			$tbody = eval($templates->render("admin/no_records"));

		// Pobranie nagłówka tabeli
		$thead = eval($templates->render("admin/services_thead"));

		if (get_privilages("manage_services"))
			// Pobranie przycisku dodającego usługę
			$buttons = create_dom_element("input", "", array(
				'id' => "service_button_add",
				'type' => "button",
				'value' => $lang->add_service
			));

		// Pobranie struktury tabeli
		$output = eval($templates->render("admin/table_structure"));
		return $output;
	}

	public function get_action_box($box_id, $data)
	{
		global $heart, $lang, $templates;

		if (!get_privilages("manage_services"))
			return array(
				'status' => "not_logged_in",
				'text' => $lang->not_logged_or_no_perm
			);

		if ($box_id == "service_edit") {
			$service = $heart->get_service($data['id']);
			$service['tag'] = htmlspecialchars($service['tag']);

			// Pobieramy pola danego modułu
			if (strlen($service['module']))
				if (($service_module = $heart->get_service_module($service['id'])) !== NULL
					&& object_implements($service_module, "IService_AdminManage")
				)
					$extra_fields = create_dom_element("tbody", $service_module->service_admin_extra_fields_get(), array(
						'class' => 'extra_fields'
					));
		} // Pobranie dostępnych modułów usług
		else if ($box_id == "service_add") {
			$services_modules = "";
			foreach ($heart->get_services_modules() as $module) {
				// Sprawdzamy czy dany moduł zezwala na tworzenie nowych usług, które będzie obsługiwał
				if (($service_module = $heart->get_service_module_s($module['id'])) === NULL
					|| !object_implements($service_module, "IService_Create")
				)
					continue;

				$services_modules .= create_dom_element("option", $module['name'], array(
					'value' => $module['id'],
					'selected' => isset($service['module']) && $service['module'] == $module['id'] ? "selected" : ""
				));
			}
		}

		// Grupy
		$groups = "";
		foreach ($heart->get_groups() as $group) {
			$groups .= create_dom_element("option", "{$group['name']} ( {$group['id']} )", array(
				'value' => $group['id'],
				'selected' => isset($service['groups']) && in_array($group['id'], $service['groups']) ? "selected" : ""
			));
		}

		switch ($box_id) {
			case "service_add":
				$output = eval($templates->render("admin/action_boxes/service_add"));
				break;

			case "service_edit":
				$service_module_name = $heart->get_service_module_name($service['module']);

				$output = eval($templates->render("admin/action_boxes/service_edit"));
				break;
		}

		return array(
			'status' => 'ok',
			'template' => $output
		);
	}

}