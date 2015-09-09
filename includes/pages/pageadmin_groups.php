<?php

use Admin\Table;
use Admin\Table\Wrapper;
use Admin\Table\Structure;
use Admin\Table\BodyRow;
use Admin\Table\Cell;
use Admin\Table\Input;

$heart->register_page("groups", "PageAdminGroups", "admin");

class PageAdminGroups extends PageAdmin implements IPageAdmin_ActionBox
{

	const PAGE_ID = "groups";
	protected $privilage = "view_groups";

	function __construct()
	{
		global $lang;
		$this->title = $lang->groups;

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $db, $lang, $G_PAGE;

		$wrapper = new Wrapper();
		$wrapper->setTitle($this->title);

		$table = new Structure();

		$cell = new Cell($lang->id);
		$cell->setParam('headers', 'id');
		$table->addHeadCell($cell);

		$table->addHeadCell(new Cell($lang->name));

		$result = $db->query(
			"SELECT SQL_CALC_FOUND_ROWS * FROM `" . TABLE_PREFIX . "groups` " .
			"LIMIT " . get_row_limit($G_PAGE)
		);

		$table->setDbRowsAmount($db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()"));

		while ($row = $db->fetch_array_assoc($result)) {
			$body_row = new BodyRow();

			$body_row->setDbId($row['id']);
			$body_row->addCell(new Cell($row['name']));
			if (get_privilages("manage_groups")) {
				$body_row->setButtonDelete(true);
				$body_row->setButtonEdit(true);
			}

			$table->addBodyRow($body_row);
		}

		$wrapper->setTable($table);

		if (get_privilages("manage_groups")) {
			$button = new Input();
			$button->setParam('id', 'group_button_add');
			$button->setParam('type', 'button');
			$button->setParam('value', $lang->add_group);
			$wrapper->addButton($button);
		}

		return $wrapper->toHtml();
	}

	public function get_action_box($box_id, $data)
	{
		global $db, $lang, $templates;

		if (!get_privilages("manage_groups")) {
			return array(
				'status' => "not_logged_in",
				'text' => $lang->not_logged_or_no_perm
			);
		}

		if ($box_id == "group_edit") {
			$result = $db->query($db->prepare(
				"SELECT * FROM `" . TABLE_PREFIX . "groups` " .
				"WHERE `id` = '%d'",
				array($data['id'])
			));

			if (!$db->num_rows($result)) {
				$data['template'] = create_dom_element("form", $lang->no_such_group, array(
					'class' => 'action_box',
					'style' => array(
						'padding' => "20px",
						'color' => "white"
					)
				));
			}
			else {
				$group = $db->fetch_array_assoc($result);
				$group['name'] = htmlspecialchars($group['name']);
			}
		}

		$privilages = "";
		$result = $db->query("DESCRIBE " . TABLE_PREFIX . "groups");
		while ($row = $db->fetch_array_assoc($result)) {
			if (in_array($row['Field'], array("id", "name"))) continue;

			$values = create_dom_element("option", $lang->strtoupper($lang->no), array(
				'value' => 0,
				'selected' => $group[$row['Field']] ? "" : "selected"
			));

			$values .= create_dom_element("option", $lang->strtoupper($lang->yes), array(
				'value' => 1,
				'selected' => $group[$row['Field']] ? "selected" : ""
			));

			$name = htmlspecialchars($row['Field']);
			$text = $lang->privilages_names[$row['Field']];

			$privilages .= eval($templates->render("tr_text_select"));
		}

		switch ($box_id) {
			case "group_add":
				$output = eval($templates->render("admin/action_boxes/group_add"));
				break;

			case "group_edit":
				$output = eval($templates->render("admin/action_boxes/group_edit"));
				break;
		}

		return array(
			'status' => 'ok',
			'template' => $output
		);
	}

}