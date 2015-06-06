<?php

$heart->register_page("admin_tariffs", "PageAdminTariffs");

class PageAdminTariffs extends PageAdmin {

	protected $privilage = "manage_settings";

	function __construct()
	{
		global $lang;
		$this->title = $lang['tariffs'];

		parent::__construct();

		global $settings, $scripts;
		$scripts[] = $settings['shop_url_slash'] . "jscripts/admin/tariffs.js?version=" . VERSION;
	}

	protected function content($get, $post) {
		global $heart, $lang;

		$i = 0;
		$tbody = "";
		foreach ($heart->get_tariffs() as $tariff_data) {
			$i += 1;
			// Pobranie przycisku edycji oraz usuwania
			$button_edit = create_dom_element("img", "", array(
				'id' => "edit_row_{$i}",
				'src' => "images/edit.png",
				'title' => "Edytuj {$tariff_data['tariff']}"
			));
			if (!$tariff_data['predefined'])
				$button_delete = create_dom_element("img", "", array(
					'id' => "delete_row_{$i}",
					'src' => "images/bin.png",
					'title' => "Usuń {$tariff_data['tariff']}"
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
		$button = array(
			'id' => "button_add_tariff",
			'value' => $lang['add_tariff']
		);
		eval("\$buttons = \"" . get_template("admin/button") . "\";");

		// Pobranie nagłówka tabeli
		eval("\$thead = \"" . get_template("admin/tariffs_thead") . "\";");

		// Pobranie struktury tabeli
		eval("\$output = \"" . get_template("admin/table_structure") . "\";");
		return $output;
	}

}