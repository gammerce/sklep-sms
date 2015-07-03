<?php

$heart->register_page("services", "PageAdminServices", "admin");

class PageAdminServices extends PageAdmin
{

	protected $privilage = "view_services";

	function __construct()
	{
		global $lang;
		$this->title = $lang->services;

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $heart, $lang, $settings, $scripts;

		// Pobranie listy serwisów transakcyjnych
		$i = 0;
		$tbody = "";
		foreach ($heart->get_services() as $row) {
			$i += 1;
			$row['id'] = htmlspecialchars($row['id']);
			$row['name'] = htmlspecialchars($row['name']);
			$row['short_description'] = htmlspecialchars($row['short_description']);
			$row['description'] = htmlspecialchars($row['description']);

			if (get_privilages("manage_services")) {
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
			eval("\$tbody .= \"" . get_template("admin/services_trow") . "\";");
		}

		// Nie ma zadnych danych do wyswietlenia
		if (!strlen($tbody))
			eval("\$tbody = \"" . get_template("admin/no_records") . "\";");

		// Pobranie nagłówka tabeli
		eval("\$thead = \"" . get_template("admin/services_thead") . "\";");

		if (get_privilages("manage_services"))
			// Pobranie przycisku dodającego usługę
			$buttons = create_dom_element("input", "", array(
				'id' => "button_add_service",
				'type' => "button",
				'value' => $lang->add_service
			));

		$scripts[] = $settings['shop_url_slash'] . "jscripts/admin/services.js?version=" . VERSION;

		// Pobranie struktury tabeli
		eval("\$output = \"" . get_template("admin/table_structure") . "\";");
		return $output;
	}

}