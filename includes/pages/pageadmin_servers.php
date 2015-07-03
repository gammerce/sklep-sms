<?php

$heart->register_page("servers", "PageAdminServers", "admin");

class PageAdminServers extends PageAdmin
{

	protected $privilage = "manage_servers";

	function __construct()
	{
		global $lang;
		$this->title = $lang->servers;

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $heart, $lang, $settings, $scripts;

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
				'id' => "button_add_server",
				'type' => "button",
				'value' => $lang->add_server
			));

		$scripts[] = $settings['shop_url_slash'] . "jscripts/admin/servers.js?version=" . VERSION;

		// Pobranie struktury tabeli
		eval("\$output = \"" . get_template("admin/table_structure") . "\";");
		return $output;
	}

}