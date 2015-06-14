<?php

$heart->register_page("players_services", "PageAdminPlayersServices", "admin");

class PageAdminPlayersServices extends PageAdmin
{

	protected $privilage = "view_player_services";

	function __construct()
	{
		global $lang;
		$this->title = $lang['players_services'];

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $heart, $db, $settings, $lang, $G_PAGE, $scripts;

		// Wyszukujemy dane ktore spelniaja kryteria
		if (isset($get['search']))
			searchWhere(array("ps.id", "ps.uid", "u.username", "srv.name", "s.name", "ps.auth_data"), urldecode($get['search']), $where);

		// Jezeli jest jakis where, to dodajemy WHERE
		if (strlen($where))
			$where = "WHERE " . $where . " ";

		$result = $db->query(
			"SELECT SQL_CALC_FOUND_ROWS ps.id AS `id`, ps.uid AS `uid`, u.username AS `username`, srv.name AS `server`, s.id AS `service_id`, s.name AS `service`, " .
			"ps.type AS `type`, ps.auth_data AS `auth_data`, ps.expire AS `expire` " .
			"FROM `" . TABLE_PREFIX . "players_services` AS ps " .
			"LEFT JOIN `" . TABLE_PREFIX . "services` AS s ON s.id = ps.service " .
			"LEFT JOIN `" . TABLE_PREFIX . "servers` AS srv ON srv.id = ps.server " .
			"LEFT JOIN `" . TABLE_PREFIX . "users` AS u ON u.uid = ps.uid " .
			$where .
			"ORDER BY `id` DESC " .
			"LIMIT " . get_row_limit($G_PAGE)
		);
		$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

		$i = 0;
		$tbody = "";
		while ($row = $db->fetch_array_assoc($result)) {
			$i += 1;
			// Zabezpieczanie danych
			$row['auth_data'] = htmlspecialchars($row['auth_data']);
			$row['service'] = htmlspecialchars($row['service']);
			$row['server'] = htmlspecialchars($row['server']);
			$row['username'] = htmlspecialchars($row['username']);

			// Zamiana daty
			$row['expire'] = $row['expire'] == -1 ? $lang['never'] : date($settings['date_format'], $row['expire']);

			// Pobranie przycisku edycji oraz usuwania
			if (get_privilages("manage_player_services")) {
				if (($service_module = $heart->get_service_module($row['service_id'])) !== NULL && class_has_interface($service_module, "IServiceAdminManageUserService"))
					$button_edit = create_dom_element("img", "", array(
						'id' => "edit_row_{$i}",
						'src' => "images/edit.png",
						'title' => $lang['edit'] . " " . $row['id']
					));
				else
					$button_edit = "";

				$button_delete = create_dom_element("img", "", array(
					'id' => "delete_row_{$i}",
					'src' => "images/bin.png",
					'title' => $lang['delete'] . " " . $row['id']
				));
			} else
				$button_edit = $button_delete = "";

			// Pobranie danych do tabeli
			eval("\$tbody .= \"" . get_template("admin/players_services_trow") . "\";");
		}

		// Nie ma zadnych danych do wyswietlenia
		if (!strlen($tbody))
			eval("\$tbody = \"" . get_template("admin/no_records") . "\";");

		// Pole wyszukiwania
		$search_text = htmlspecialchars($get['search']);
		eval("\$buttons = \"" . get_template("admin/form_search") . "\";");

		// Pobranie przycisku dodajacego flagi
		if (get_privilages("manage_player_services"))
			$buttons .= create_dom_element("input", "", array(
				'id' => "button_add_user_service",
				'type' => "button",
				'value' => $lang['add_user_service']
			));

		// Pobranie paginacji
		$pagination = get_pagination($rows_count, $G_PAGE, "admin.php", $get);
		if (strlen($pagination))
			$tfoot_class = "display_tfoot";

		// Pobranie nagłówka tabeli
		eval("\$thead = \"" . get_template("admin/players_services_thead") . "\";");

		$scripts[] = $settings['shop_url_slash'] . "jscripts/admin/players_services.js?version=" . VERSION;
		$scripts[] = $settings['shop_url_slash'] . "jscripts/services/extra_flags.js?version=" . VERSION;

		// Pobranie struktury tabeli
		eval("\$output = \"" . get_template("admin/table_structure") . "\";");
		return $output;
	}

}