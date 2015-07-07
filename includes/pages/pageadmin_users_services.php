<?php

$heart->register_page("users_services", "PageAdminUsersServices", "admin");

class PageAdminUsersServices extends PageAdmin implements IPageAdminActionBox
{

	const PAGE_ID = "users_services";
	protected $privilage = "view_user_services";

	function __construct()
	{
		global $lang;
		$this->title = $lang->users_services;

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $heart, $db, $settings, $lang, $G_PAGE;

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
			$row['expire'] = $row['expire'] == -1 ? $lang->never : date($settings['date_format'], $row['expire']);

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
			eval("\$tbody .= \"" . get_template("admin/users_services_trow") . "\";");
		}

		// Nie ma zadnych danych do wyswietlenia
		if (!strlen($tbody))
			eval("\$tbody = \"" . get_template("admin/no_records") . "\";");

		// Pole wyszukiwania
		$search_text = htmlspecialchars($get['search']);
		eval("\$buttons = \"" . get_template("admin/form_search") . "\";");

		// Pobranie przycisku dodajacego flagi
		$buttons = "";
		if (get_privilages("manage_user_services"))
			$buttons .= create_dom_element("input", "", array(
				'id' => "button_add_user_service",
				'type' => "button",
				'value' => $lang->add_service
			));

		// Pobranie paginacji
		$pagination = get_pagination($rows_count, $G_PAGE, "admin.php", $get);
		if (strlen($pagination))
			$tfoot_class = "display_tfoot";

		// Pobranie nagłówka tabeli
		eval("\$thead = \"" . get_template("admin/users_services_thead") . "\";");

		// Pobranie struktury tabeli
		eval("\$output = \"" . get_template("admin/table_structure") . "\";");
		return $output;
	}

	public function get_action_box($box_id, $data)
	{
		global $heart, $db, $lang;

		if (!get_privilages("manage_user_services"))
			return array(
				'id'	=> "not_logged_in",
				'text'	=> $lang->not_logged_or_no_perm
			);

		switch($box_id) {
			case "add_user_service":
				// Pobranie usług
				$services = "";
				foreach ($heart->get_services() as $id => $row) {
					if (($service_module = $heart->get_service_module($id)) === NULL || !object_implements($service_module, "IService_UserServiceAdminManage"))
						continue;

					$services .= create_dom_element("option", $row['name'], array(
						'value' => $row['id']
					));
				}

				eval("\$output = \"" . get_template("admin/action_boxes/user_service_add") . "\";");
				break;

			case "edit_user_service":
				// Pobieramy usługę z bazy
				$user_service = $db->fetch_array_assoc($db->query($db->prepare(
					"SELECT * FROM `" . TABLE_PREFIX . "players_services` " .
					"WHERE `id` = '%d'",
					array($data['id'])
				)));

				if (($service_module = $heart->get_service_module($user_service['service'])) !== NULL) {
					$service_module_id = htmlspecialchars($service_module::MODULE_ID);
					$form_data = $service_module->user_service_admin_edit_form_get($user_service);
				}

				if (!isset($form_data) || !strlen($form_data))
					$form_data = $lang->service_edit_unable;

				eval("\$output = \"" . get_template("admin/action_boxes/user_service_edit") . "\";");
				break;
		}

		return array(
			'id'		=> "ok",
			'template'	=> $output
		);
	}
}