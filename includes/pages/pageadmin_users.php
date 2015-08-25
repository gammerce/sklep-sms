<?php

$heart->register_page("users", "PageAdminUsers", "admin");

class PageAdminUsers extends PageAdmin implements IPageAdmin_ActionBox
{

	const PAGE_ID = "users";
	protected $privilage = "view_users";

	function __construct()
	{
		global $lang;
		$this->title = $lang->users;

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $heart, $db, $settings, $lang, $G_PAGE, $templates;

		// Wyszukujemy dane ktore spelniaja kryteria
		if (isset($get['search']))
			searchWhere(array("`uid`", "`username`", "`forename`", "`surname`", "`email`", "`groups`", "`wallet`"), $get['search'], $where);

		// Jezeli jest jakis where, to dodajemy WHERE
		if (strlen($where))
			$where = "WHERE " . $where . " ";

		$result = $db->query(
			"SELECT SQL_CALC_FOUND_ROWS `uid`, `username`, `forename`, `surname`, `email`, `groups`, `wallet` " .
			"FROM `" . TABLE_PREFIX . "users` " .
			$where .
			"LIMIT " . get_row_limit($G_PAGE)
		);
		$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

		$i = 0;
		$tbody = "";
		while ($row = $db->fetch_array_assoc($result)) {
			$i += 1;
			// Zabezpieczanie danych
			$row['username'] = htmlspecialchars($row['username']);
			$row['email'] = htmlspecialchars($row['email']);
			$row['forename'] = htmlspecialchars($row['forename']);
			$row['surname'] = htmlspecialchars($row['surname']);
			$row['wallet'] = number_format($row['wallet'] / 100.0, 2);


			$row['groups'] = explode(";", $row['groups']);
			$groups = array();
			foreach ($row['groups'] as $gid) {
				$group = $heart->get_group($gid);
				$groups[] = "{$group['name']} ({$group['id']})";
			}
			$groups = implode("; ", $groups);

			// Pobranie przycisku doładowania portfela
			if (get_privilages("manage_users")) {
				$button_charge = eval($templates->render("admin/users_button_charge"));
				$button_edit = create_dom_element("img", "", array(
					'id' => "edit_row_{$i}",
					'src' => "images/edit.png",
					'title' => $lang->edit . " " . $row['username']
				));
				$button_delete = create_dom_element("img", "", array(
					'id' => "delete_row_{$i}",
					'src' => "images/bin.png",
					'title' => $lang->delete . " " . $row['username']
				));
			} else
				$button_charge = $button_delete = $button_edit = "";

			// Pobranie danych do tabeli
			$tbody .= eval($templates->render("admin/users_trow"));
		}

		// Nie ma zadnych danych do wyswietlenia
		if (!strlen($tbody))
			$tbody = eval($templates->render("admin/no_records"));

		// Pole wyszukiwania
		$search_text = htmlspecialchars($get['search']);
		$buttons = eval($templates->render("admin/form_search"));

		// Pobranie paginacji
		$pagination = get_pagination($rows_count, $G_PAGE, "admin.php", $get);
		if (strlen($pagination))
			$tfoot_class = "display_tfoot";

		// Pobranie nagłówka tabeli
		$thead = eval($templates->render("admin/users_thead"));

		// Pobranie struktury tabeli
		$output = eval($templates->render("admin/table_structure"));
		return $output;
	}

	public function get_action_box($box_id, $data)
	{
		global $heart, $settings, $lang, $templates;

		if (!get_privilages("manage_users"))
			return array(
				'status' => "not_logged_in",
				'text' => $lang->not_logged_or_no_perm
			);

		switch ($box_id) {
			case "user_edit":
				// Pobranie użytkownika
				$row = $heart->get_user($data['uid']);

				$groups = "";
				foreach ($heart->get_groups() as $group) {
					$groups .= create_dom_element("option", "{$group['name']} ( {$group['id']} )", array(
						'value' => $group['id'],
						'selected' => in_array($group['id'], $row['groups']) ? "selected" : ""
					));
				}

				$output = eval($templates->render("admin/action_boxes/user_edit"));
				break;

			case "charge_wallet":
				$user = $heart->get_user($data['uid']);

				$output = eval($templates->render("admin/action_boxes/user_charge_wallet"));
				break;
		}

		return array(
			'status' => 'ok',
			'template' => $output
		);
	}

}