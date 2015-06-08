<?php

$heart->register_page("tariffs", "PageAdminTariffs", "admin");

class PageAdminTariffs extends PageAdmin {

	protected $privilage = "manage_settings";

	function __construct()
	{
		global $lang;
		$this->title = $lang['tariffs'];

		parent::__construct();
	}

	protected function content($get, $post) {
		global $heart, $lang, $settings, $scripts;

		$i = 0;
		$tbody = "";
		foreach ($heart->get_tariffs() as $tariff_data) {
			$i += 1;
			// Pobranie przycisku edycji oraz usuwania
			$button_edit = create_dom_element("img", "", array(
				'id' => "edit_row_{$i}",
				'src' => "images/edit.png",
				'title' => $lang['edit']. " " . $tariff_data['tariff']
			));
			if (!$tariff_data['predefined'])
				$button_delete = create_dom_element("img", "", array(
					'id' => "delete_row_{$i}",
					'src' => "images/bin.png",
					'title' => $lang['delete']. " " . $tariff_data['tariff']
				));
			else
				$button_delete = "";

			$provision = number_format($tariff_data['provision'], 2);

			// Pobranie danych do tabeli
			eval("\$tbody .= \"" . get_template("admin/tariffs_trow") . "\";");
		}

		// Nie ma zadnych danych do wyswietlenia
		if (!strlen($tbody))
			eval("\$tbody = \"" . get_template("admin/no_records") . "\";");

		// Pobranie przycisku dodającego taryfę
		$buttons = create_dom_element("input", "", array(
			'id' => "button_add_tariff",
			'type' => "button",
			'value' => $lang['add_tariff']
		));

		// Pobranie nagłówka tabeli
		eval("\$thead = \"" . get_template("admin/tariffs_thead") . "\";");

		$scripts[] = $settings['shop_url_slash'] . "jscripts/admin/tariffs.js?version=" . VERSION;

		// Pobranie struktury tabeli
		eval("\$output = \"" . get_template("admin/table_structure") . "\";");
		return $output;
	}

}