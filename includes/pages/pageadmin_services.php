<?php

use Admin\Table;
use Admin\Table\Wrapper;
use Admin\Table\Structure;
use Admin\Table\BodyRow;
use Admin\Table\Cell;
use Admin\Table\Input;

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
		global $heart, $lang;

		$wrapper = new Wrapper();
		$wrapper->setTitle($this->title);

		$table = new Structure();

		$cell = new Cell($lang->id);
		$cell->setParam('headers', 'id');
		$table->addHeadCell($cell);

		$table->addHeadCell(new Cell($lang->name));
		$table->addHeadCell(new Cell($lang->short_description));
		$table->addHeadCell(new Cell($lang->description));
		$table->addHeadCell(new Cell($lang->order));

		foreach ($heart->get_services() as $row) {
			$body_row = new BodyRow();

			$body_row->setDbId(htmlspecialchars($row['id']));

			$cell = new Cell(htmlspecialchars($row['name']));
			$cell->setParam('headers', 'name');
			$body_row->addCell($cell);
			$body_row->addCell(new Cell(htmlspecialchars($row['short_description'])));
			$body_row->addCell(new Cell(htmlspecialchars($row['description'])));
			$body_row->addCell(new Cell($row['order']));

			if (get_privilages('manage_services')) {
				$body_row->setButtonDelete(true);
				$body_row->setButtonEdit(true);
			}

			$table->addBodyRow($body_row);
		}

		$wrapper->setTable($table);

		if (get_privilages('manage_services')) {
			$button = new Input();
			$button->setParam('id', 'service_button_add');
			$button->setParam('type', 'button');
			$button->setParam('value', $lang->add_service);
			$wrapper->addButton($button);
		}

		return $wrapper->toHtml();
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