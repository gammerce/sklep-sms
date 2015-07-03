<?php

$heart->register_page("sms_codes", "PageAdminSmsCodes", "admin");

class PageAdminSmsCodes extends PageAdmin
{

	protected $privilage = "view_sms_codes";

	function __construct()
	{
		global $lang;
		$this->title = $lang->sms_codes;

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $db, $lang, $G_PAGE, $settings, $scripts;

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
			eval("\$tbody .= \"" . get_template("admin/sms_codes_trow") . "\";");
		}

		// Nie ma zadnych danych do wyswietlenia
		if (!strlen($tbody))
			eval("\$tbody = \"" . get_template("admin/no_records") . "\";");

		if (get_privilages("manage_sms_codes"))
			$buttons = create_dom_element("input", "", array(
				'id' => "button_add_sms_code",
				'type' => "button",
				'value' => $lang->add_sms_code
			));

		// Pobranie paginacji
		$pagination = get_pagination($rows_count, $G_PAGE, "admin.php", $get);
		if (strlen($pagination))
			$tfoot_class = "display_tfoot";

		// Pobranie nagłówka tabeli
		eval("\$thead = \"" . get_template("admin/sms_codes_thead") . "\";");

		$scripts[] = $settings['shop_url_slash'] . "jscripts/admin/sms_codes.js?version=" . VERSION;

		// Pobranie wygladu całej tabeli
		eval("\$output = \"" . get_template("admin/table_structure") . "\";");
		return $output;
	}

}