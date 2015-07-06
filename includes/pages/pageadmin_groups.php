<?php

$heart->register_page("groups", "PageAdminGroups", "admin");

class PageAdminGroups extends PageAdmin implements IPageAdminActionBox
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

		$result = $db->query(
			"SELECT SQL_CALC_FOUND_ROWS * FROM `" . TABLE_PREFIX . "groups` " .
			"LIMIT " . get_row_limit($G_PAGE)
		);
		$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

		$i = 0;
		$tbody = "";
		while ($row = $db->fetch_array_assoc($result)) {
			$i += 1;

			if (get_privilages("manage_groups")) {
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

			$row['name'] = htmlspecialchars($row['name']);

			// Pobranie danych do tabeli
			eval("\$tbody .= \"" . get_template("admin/groups_trow") . "\";");
		}

		// Nie ma zadnych danych do wyswietlenia
		if (!strlen($tbody))
			eval("\$tbody = \"" . get_template("admin/no_records") . "\";");

		// Pobranie paginacji
		$pagination = get_pagination($rows_count, $G_PAGE, "admin.php", $get);
		if (strlen($pagination))
			$tfoot_class = "display_tfoot";

		// Pobranie nagłówka tabeli
		eval("\$thead = \"" . get_template("admin/groups_thead") . "\";");

		if (get_privilages("manage_groups"))
			// Pobranie przycisku dodającego grupę
			$buttons = create_dom_element("input", "", array(
				'id' => "button_add_group",
				'type' => "button",
				'value' => $lang->add_group
			));

		// Pobranie struktury tabeli
		eval("\$output = \"" . get_template("admin/table_structure") . "\";");
		return $output;
	}

	public function get_action_box($box_id, $data)
	{
		global $db, $lang;

		if (!get_privilages("manage_groups"))
			return array(
				'id'	=> "not_logged_in",
				'text'	=> $lang->not_logged_or_no_perm
			);

		if ($box_id == "edit_group") {
			$result = $db->query($db->prepare(
				"SELECT * FROM `" . TABLE_PREFIX . "groups` " .
				"WHERE `id` = '%d'",
				array($data['id'])
			));

			if (!$db->num_rows($result))
				$data['template'] = create_dom_element("form", $lang->no_such_group, array(
					'class' => 'action_box',
					'style' => array(
						'padding' => "20px",
						'color' => "white"
					)
				));
			else {
				$group = $db->fetch_array_assoc($result);
				$group['name'] = htmlspecialchars($group['name']);
			}
		}

		$result = $db->query("DESCRIBE " . TABLE_PREFIX . "groups");
		while ($row = $db->fetch_array_assoc($result)) {
			if (in_array($row['Field'], array("id", "name"))) continue;

			$values = create_dom_element("option", strtoupper($lang->no), array(
				'value' => 0,
				'selected' => $group[$row['Field']] ? "" : "selected"
			));

			$values .= create_dom_element("option", strtoupper($lang->yes), array(
				'value' => 1,
				'selected' => $group[$row['Field']] ? "selected" : ""
			));

			$name = htmlspecialchars($row['Field']);
			$text = $lang->privilages_names[$row['Field']];

			eval("\$privilages .= \"" . get_template("tr_text_select") . "\";");
		}

		switch($box_id) {
			case "add_group":
				eval("\$output = \"" . get_template("admin/action_boxes/group_add") . "\";");
				break;

			case "edit_group":
				eval("\$output = \"" . get_template("admin/action_boxes/group_edit") . "\";");
				break;
		}

		return array(
			'id'		=> "ok",
			'template'	=> $output
		);
	}

}