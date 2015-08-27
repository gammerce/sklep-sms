<?php

$heart->register_page("tariffs", "PageAdminTariffs", "admin");

class PageAdminTariffs extends PageAdmin implements IPageAdmin_ActionBox
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
		global $heart, $lang, $settings, $templates; // settings potrzebne w pliku trow

		$i = 0;
		$tbody = "";
		foreach ($heart->getTariffs() as $tariff) {
			$i += 1;
			// Pobranie przycisku edycji oraz usuwania
			$button_edit = create_dom_element("img", "", array(
				'id' => "edit_row_{$i}",
				'src' => "images/edit.png",
				'title' => $lang->edit . " " . $tariff->getId()
			));
			if (!$tariff->isPredefined())
				$button_delete = create_dom_element("img", "", array(
					'id' => "delete_row_{$i}",
					'src' => "images/bin.png",
					'title' => $lang->delete . " " . $tariff->getId()
				));
			else
				$button_delete = "";

			$provision = number_format($tariff->getProvision() / 100.0, 2);

			// Pobranie danych do tabeli
			$tbody .= eval($templates->render("admin/tariffs_trow"));
		}

		// Nie ma zadnych danych do wyswietlenia
		if (!strlen($tbody))
			$tbody = eval($templates->render("admin/no_records"));

		// Pobranie przycisku dodającego taryfę
		$buttons = create_dom_element("input", "", array(
			'id' => "tariff_button_add",
			'type' => "button",
			'value' => $lang->add_tariff
		));

		// Pobranie nagłówka tabeli
		$thead = eval($templates->render("admin/tariffs_thead"));

		// Pobranie struktury tabeli
		$output = eval($templates->render("admin/table_structure"));
		return $output;
	}

	public function get_action_box($box_id, $data)
	{
		global $heart, $lang, $settings, $templates; // settings potrzebne

		if (!get_privilages("manage_settings"))
			return array(
				'status' => "not_logged_in",
				'text' => $lang->not_logged_or_no_perm
			);

		switch ($box_id) {
			case "tariff_add":
				$output = eval($templates->render("admin/action_boxes/tariff_add"));
				break;

			case "tariff_edit":
				$tariff = $heart->getTariff($data['tariff']);
				$provision = number_format($tariff->getProvision() / 100.0, 2);

				$output = eval($templates->render("admin/action_boxes/tariff_edit"));
				break;
		}

		return array(
			'status' => 'ok',
			'template' => $output
		);
	}

}