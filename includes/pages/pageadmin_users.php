<?php

$heart->register_page("users", "PageAdminUsers", "admin");

class PageAdminUsers extends PageAdmin implements IPageAdminActionBox
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
		global $heart, $db, $lang, $G_PAGE;

		// Wyszukujemy dane ktore spelniaja kryteria
		if (isset($get['search']))
			searchWhere(array("`uid`", "`username`", "`forename`", "`surname`", "`email`", "`groups`", "`wallet`"), urldecode($get['search']), $where);

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
			$row['wallet'] = number_format($row['wallet'], 2);


			$row['groups'] = explode(";", $row['groups']);
			$groups = array();
			foreach ($row['groups'] as $gid) {
				$group = $heart->get_group($gid);
				$groups[] = "{$group['name']} ({$group['id']})";
			}
			$groups = implode("; ", $groups);

			// Pobranie przycisku doładowania portfela
			if (get_privilages("manage_users")) {
				eval("\$button_charge = \"" . get_template("admin/users_button_charge") . "\";");
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
			eval("\$tbody .= \"" . get_template("admin/users_trow") . "\";");
		}

		// Nie ma zadnych danych do wyswietlenia
		if (!strlen($tbody))
			eval("\$tbody = \"" . get_template("admin/no_records") . "\";");

		// Pole wyszukiwania
		$search_text = htmlspecialchars($get['search']);
		eval("\$buttons = \"" . get_template("admin/form_search") . "\";");

		// Pobranie paginacji
		$pagination = get_pagination($rows_count, $G_PAGE, "admin.php", $get);
		if (strlen($pagination))
			$tfoot_class = "display_tfoot";

		// Pobranie nagłówka tabeli
		eval("\$thead = \"" . get_template("admin/users_thead") . "\";");

		// Pobranie struktury tabeli
		eval("\$output = \"" . get_template("admin/table_structure") . "\";");
		return $output;
	}

	public function get_action_box($box_id, $data)
	{
		global $heart, $lang;

		if (!get_privilages("manage_users"))
			return array(
				'id'	=> "not_logged_in",
				'text'	=> $lang->not_logged_or_no_perm
			);

		switch($box_id) {
			case "edit_user":
				// Pobranie użytkownika
				$row = $heart->get_user($data['uid']);

				$groups = "";
				foreach ($heart->get_groups() as $group) {
					$groups .= create_dom_element("option", "{$group['name']} ( {$group['id']} )", array(
						'value' => $group['id'],
						'selected' => in_array($group['id'], $row['groups']) ? "selected" : ""
					));
				}

				eval("\$output = \"" . get_template("admin/action_boxes/user_edit") . "\";");
				break;

			case "charge_wallet":
				$user = $heart->get_user($data['uid']);

				eval("\$output = \"" . get_template("admin/action_boxes/user_charge_wallet") . "\";");
				break;
		}

		return array(
			'id'		=> "ok",
			'template'	=> $output
		);
	}

}