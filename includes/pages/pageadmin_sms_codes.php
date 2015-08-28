<?php

$heart->register_page("sms_codes", "PageAdminSmsCodes", "admin");

class PageAdminSmsCodes extends PageAdmin implements IPageAdmin_ActionBox
{

	const PAGE_ID = "sms_codes";
	protected $privilage = "view_sms_codes";

	function __construct()
	{
		global $lang;
		$this->title = $lang->sms_codes;

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $db, $lang, $G_PAGE, $templates;

		// Pobranie kodów SMS
		$result = $db->query(
			"SELECT SQL_CALC_FOUND_ROWS * " .
			"FROM `" . TABLE_PREFIX . "sms_codes` " .
			"WHERE `free` = '1' " .
			"LIMIT " . get_row_limit($G_PAGE)
		);
		$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

		$i = 0;
		$tbody = "";
		while ($row = $db->fetch_array_assoc($result)) {
			$i += 1;
			// Pobranie przycisku usuwania
			if (get_privilages("manage_sms_codes"))
				$button_delete = create_dom_element("img", "", array(
					'id' => "delete_row_{$i}",
					'src' => "images/bin.png",
					'title' => $lang->delete . " " . $row['id']
				));
			else
				$button_delete = "";

			// Zabezpieczanie danych
			$row['code'] = htmlspecialchars($row['code']);

			// Pobranie danych do tabeli
			$tbody .= eval($templates->render("admin/sms_codes_trow"));
		}

		// Nie ma zadnych danych do wyswietlenia
		if (!strlen($tbody))
			$tbody = eval($templates->render("admin/no_records"));

		if (get_privilages("manage_sms_codes"))
			$buttons = create_dom_element("input", "", array(
				'id' => "sms_code_button_add",
				'type' => "button",
				'value' => $lang->add_code
			));

		// Pobranie paginacji
		$pagination = get_pagination($rows_count, $G_PAGE, "admin.php", $get);
		if (strlen($pagination))
			$tfoot_class = "display_tfoot";

		// Pobranie nagłówka tabeli
		$thead = eval($templates->render("admin/sms_codes_thead"));

		// Pobranie wygladu całej tabeli
		$output = eval($templates->render("admin/table_structure"));
		return $output;
	}

	public function get_action_box($box_id, $data)
	{
		global $heart, $lang, $templates;

		if (!get_privilages("manage_sms_codes"))
			return array(
				'status' => "not_logged_in",
				'text' => $lang->not_logged_or_no_perm
			);

		switch ($box_id) {
			case "sms_code_add":
				$tariffs = "";
				foreach ($heart->getTariffs() as $tariff) {
					$tariffs .= create_dom_element("option", $tariff->getId(), array(
						'value' => $tariff->getId()
					));
				}

				$output = eval($templates->render("admin/action_boxes/sms_code_add"));
				break;
		}

		return array(
			'status' => 'ok',
			'template' => $output
		);
	}

}