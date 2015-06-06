<?php

$heart->register_page("admin_users", "PageAdminUsers");

class PageAdminUsers extends PageAdmin {

	protected $privilage = "view_users";

	function __construct()
	{
		global $lang;
		$this->title = $lang['users'];

		parent::__construct();

		global $settings, $scripts;
		$scripts[] = $settings['shop_url_slash'] . "jscripts/admin/users.js?version=" . VERSION;
	}

	protected function content($get, $post) {
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
					'title' => "Edytuj {$row['username']}"
				));
				$button_delete = create_dom_element("img", "", array(
					'id' => "delete_row_{$i}",
					'src' => "images/bin.png",
					'title' => "Usuń {$row['username']}"
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

}