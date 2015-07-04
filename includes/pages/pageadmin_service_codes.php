<?php

$heart->register_page("service_codes", "PageAdminServiceCodes", "admin");

class PageAdminServiceCodes extends PageAdmin
{

	protected $privilage = "view_service_codes";

	function __construct()
	{
		global $lang;
		$this->title = $lang->service_codes;

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $db, $lang, $G_PAGE, $settings, $scripts;

		$result = $db->query(
			"SELECT SQL_CALC_FOUND_ROWS *, sc.id, sc.code, s.name AS `service`, srv.name AS `server`, sc.tariff, u.username, sc.amount, sc.data, sc.timestamp " .
			"FROM `" . TABLE_PREFIX . "service_codes` AS sc " .
			"JOIN `" . TABLE_PREFIX . "services` AS s ".
			"JOIN `" . TABLE_PREFIX . "servers` AS srv ".
			"JOIN `" . TABLE_PREFIX . "users` AS u ".
			"LIMIT " . get_row_limit($G_PAGE)
		);
		$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

		$i = 0;
		$tbody = "";
		while ($row = $db->fetch_array_assoc($result)) {
			$i += 1;
			// Pobranie przycisku usuwania
			if (get_privilages("manage_sservice_codes"))
				$button_delete = create_dom_element("img", "", array(
					'id' => "delete_row_{$i}",
					'src' => "images/bin.png",
					'title' => $lang->delete . " " . $row['id']
				));
			else
				$button_delete = "";

			// Zabezpieczanie danych
			foreach($row AS $key => $value)
				$row[$key] = htmlspecialchars($value);

			// Pobranie danych do tabeli
			eval("\$tbody .= \"" . get_template("admin/service_codes_trow") . "\";");
		}

		// Nie ma zadnych danych do wyswietlenia
		if (!strlen($tbody))
			eval("\$tbody = \"" . get_template("admin/no_records") . "\";");

		if (get_privilages("manage_service_codes"))
			$buttons = create_dom_element("input", "", array(
				'id' => "button_add_service_code",
				'type' => "button",
				'value' => $lang->add_code
			));

		// Pobranie paginacji
		$pagination = get_pagination($rows_count, $G_PAGE, "admin.php", $get);
		if (strlen($pagination))
			$tfoot_class = "display_tfoot";

		// Pobranie nagłówka tabeli
		eval("\$thead = \"" . get_template("admin/service_codes_thead") . "\";");

		$scripts[] = $settings['shop_url_slash'] . "jscripts/admin/service_codes.js?version=" . VERSION;

		// Pobranie wygladu całej tabeli
		eval("\$output = \"" . get_template("admin/table_structure") . "\";");
		return $output;
	}

}