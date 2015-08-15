<?php

class PageAdminUserService_ExtraFlags implements IPageAdmin_UserService
{

	const PAGE_ID = "user_service_extra_flags";

	public function get_title() {
		global $lang;
		return $lang->user_service_extra_flags;
	}

	public function get_content($get, $post)
	{
		global $heart, $db, $settings, $lang, $G_PAGE, $templates;

		// Wyszukujemy dane ktore spelniaja kryteria
		$where = "";
		if (isset($get['search']))
			searchWhere(array("us.id", "us.uid", "u.username", "srv.name", "s.name", "usef.auth_data"), urldecode($get['search']), $where);
		// Jezeli jest jakis where, to dodajemy WHERE
		if (strlen($where))
			$where = "WHERE " . $where . " ";

		$result = $db->query(
			"SELECT SQL_CALC_FOUND_ROWS us.id AS `id`, us.uid AS `uid`, u.username AS `username`, " .
			"srv.name AS `server`, s.id AS `service_id`, s.name AS `service`, " .
			"usef.type AS `type`, usef.auth_data AS `auth_data`, us.expire AS `expire` " .
			"FROM `" . TABLE_PREFIX . "user_service` AS us " .
			"INNER JOIN `" . TABLE_PREFIX . "user_service_extra_flags` AS usef ON usef.us_id = us.id " .
			"LEFT JOIN `" . TABLE_PREFIX . "services` AS s ON s.id = usef.service " .
			"LEFT JOIN `" . TABLE_PREFIX . "servers` AS srv ON srv.id = usef.server " .
			"LEFT JOIN `" . TABLE_PREFIX . "users` AS u ON u.uid = us.uid " .
			$where .
			"ORDER BY us.id DESC " .
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
			$username = $row['uid'] ? htmlspecialchars($row['username']) . " ({$row['uid']})" : $lang->none;

			// Zamiana daty
			$row['expire'] = $row['expire'] === NULL ? $lang->never : date($settings['date_format'], $row['expire']);

			// Pobranie przycisku edycji oraz usuwania
			if (get_privilages("manage_user_services")) {
				if (($service_module = $heart->get_service_module($row['service_id'])) !== NULL && object_implements($service_module, "IService_UserServiceAdminManage"))
					$button_edit = create_dom_element("img", "", array(
						'id' => "edit_row_{$i}",
						'src' => "images/edit.png",
						'title' => $lang->edit . " " . $row['id']
					));
				else
					$button_edit = "";

				$button_delete = create_dom_element("img", "", array(
					'id' => "delete_row_{$i}",
					'src' => "images/bin.png",
					'title' => $lang->delete . " " . $row['id']
				));
			} else
				$button_edit = $button_delete = "";

			// Pobranie danych do tabeli
			$tbody .= eval($templates->render("admin/user_service_extra_flags_trow"));
		}

		// Nie ma zadnych danych do wyswietlenia
		if (!strlen($tbody))
			$tbody = eval($templates->render("admin/no_records"));

		// Pole wyszukiwania
		$search_text = htmlspecialchars($get['search']);
		$buttons = eval($templates->render("admin/form_search"));

		// Pobranie paginacji
		$pagination = get_pagination($rows_count, $G_PAGE, "admin.php", $get);
		$tfoot_class = strlen($pagination) ? "display_tfoot" : "";

		// Pobranie nagłówka tabeli
		$thead = eval($templates->render("admin/user_service_extra_flags_thead"));

		return array(
			'thead' => $thead,
			'tbody' => $tbody,
			'tfoot_class' => $tfoot_class,
			'pagination' => $pagination,
			'buttons' => $buttons
		);
	}
}