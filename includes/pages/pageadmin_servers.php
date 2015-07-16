<?php

$heart->register_page("servers", "PageAdminServers", "admin");

class PageAdminServers extends PageAdmin implements IPageAdminActionBox
{

	const PAGE_ID = "servers";
	protected $privilage = "manage_servers";

	function __construct()
	{
		global $lang;
		$this->title = $lang->servers;

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $heart, $lang, $settings;

		$i = 0;
		$tbody = "";
		foreach ($heart->get_servers() as $row) {
			$i += 1;
			$row['name'] = htmlspecialchars($row['name']);
			$row['ip'] = htmlspecialchars($row['ip']);
			$row['port'] = htmlspecialchars($row['port']);

			if (get_privilages("manage_servers")) {
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
			eval("\$tbody .= \"" . get_template("admin/servers_trow") . "\";");
		}

		// Nie ma zadnych danych do wyswietlenia
		if (!strlen($tbody))
			eval("\$tbody = \"" . get_template("admin/no_records") . "\";");

		// Pobranie nagłówka tabeli
		eval("\$thead = \"" . get_template("admin/servers_thead") . "\";");

		if (get_privilages("manage_servers"))
			// Pobranie przycisku dodającego serwer
			$buttons = create_dom_element("input", "", array(
				'id' => "server_button_add",
				'type' => "button",
				'value' => $lang->add_server
			));

		// Pobranie struktury tabeli
		eval("\$output = \"" . get_template("admin/table_structure") . "\";");
		return $output;
	}

	public function get_action_box($box_id, $data)
	{
		global $heart, $db, $lang;

		if (!get_privilages("manage_servers"))
			return array(
				'id'	=> "not_logged_in",
				'text'	=> $lang->not_logged_or_no_perm
			);

		if ($box_id == "server_edit") {
			$server = $heart->get_server($data['id']);
			$server['ip'] = htmlspecialchars($server['ip']);
			$server['port'] = htmlspecialchars($server['port']);
		}

		// Pobranie listy serwisów transakcyjnych
		$result = $db->query(
			"SELECT `id`, `name`, `sms` " .
			"FROM `" . TABLE_PREFIX . "transaction_services`"
		);
		$sms_services = "";
		while ($row = $db->fetch_array_assoc($result)) {
			if (!$row['sms'])
				continue;

			$sms_services .= create_dom_element("option", $row['name'], array(
				'value' => $row['id'],
				'selected' => $row['id'] == $server['sms_service'] ? "selected" : ""
			));
		}


		foreach ($heart->get_services() as $service) {
			// Dana usługa nie może być kupiona na serwerze
			if (!is_null($service_module = $heart->get_service_module($service['id'])) && !object_implements($service_module, "IService_AvailableOnServers"))
				continue;

			$values = create_dom_element("option", strtoupper($lang->no), array(
				'value' => 0,
				'selected' => $heart->server_service_linked($server['id'], $service['id']) ? "" : "selected"
			));

			$values .= create_dom_element("option", strtoupper($lang->yes), array(
				'value' => 1,
				'selected' => $heart->server_service_linked($server['id'], $service['id']) ? "selected" : ""
			));

			$name = htmlspecialchars($service['id']);
			$text = htmlspecialchars("{$service['name']} ( {$service['id']} )");

			eval("\$services .= \"" . get_template("tr_text_select") . "\";");
		}

		switch($box_id) {
			case "server_add":
				eval("\$output = \"" . get_template("admin/action_boxes/server_add") . "\";");
				break;

			case "server_edit":
				eval("\$output = \"" . get_template("admin/action_boxes/server_edit") . "\";");
				break;
		}

		return array(
			'id'		=> "ok",
			'template'	=> $output
		);
	}

}