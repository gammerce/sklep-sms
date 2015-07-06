<?php

$heart->register_page("tariffs", "PageAdminTariffs", "admin");

class PageAdminTariffs extends PageAdmin implements IPageAdminActionBox
{

	const PAGE_ID = "tariffs";
	protected $privilage = "manage_settings";

	function __construct()
	{
		global $lang;
		$this->title = $lang->tariffs;

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $heart, $lang, $settings; // settings potrzebne w pliku trow

		$i = 0;
		$tbody = "";
		foreach ($heart->get_tariffs() as $tariff_data) {
			$i += 1;
			// Pobranie przycisku edycji oraz usuwania
			$button_edit = create_dom_element("img", "", array(
				'id' => "edit_row_{$i}",
				'src' => "images/edit.png",
				'title' => $lang->edit . " " . $tariff_data['tariff']
			));
			if (!$tariff_data['predefined'])
				$button_delete = create_dom_element("img", "", array(
					'id' => "delete_row_{$i}",
					'src' => "images/bin.png",
					'title' => $lang->delete . " " . $tariff_data['tariff']
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
			'value' => $lang->add_tariff
		));

		// Pobranie nagłówka tabeli
		eval("\$thead = \"" . get_template("admin/tariffs_thead") . "\";");

		// Pobranie struktury tabeli
		eval("\$output = \"" . get_template("admin/table_structure") . "\";");
		return $output;
	}

	public function get_action_box($box_id, $data)
	{
		global $heart, $lang, $settings; // settings potrzebne

		if (!get_privilages("manage_settings"))
			return array(
				'id'	=> "not_logged_in",
				'text'	=> $lang->not_logged_or_no_perm
			);

		switch($box_id) {
			case "add_tariff":
				eval("\$output = \"" . get_template("admin/action_boxes/tariff_add") . "\";");
				break;

			case "edit_tariff":
				$tariff = htmlspecialchars($data['tariff']);
				$provision = number_format($heart->get_tariff_provision($data['tariff']), 2);

				eval("\$output = \"" . get_template("admin/action_boxes/tariff_edit") . "\";");
				break;
		}

		return array(
			'id'		=> "ok",
			'template'	=> $output
		);
	}

}