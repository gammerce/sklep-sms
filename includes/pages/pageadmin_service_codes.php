<?php

$heart->register_page("service_codes", "PageAdminServiceCodes", "admin");

class PageAdminServiceCodes extends PageAdmin implements IPageAdminActionBox
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
		global $heart, $db, $lang, $G_PAGE, $settings, $scripts;

		$result = $db->query(
			"SELECT SQL_CALC_FOUND_ROWS *, sc.id, sc.code, s.name AS `service`, srv.name AS `server`, sc.tariff, pl.amount AS `tariff_amount`,
			u.username, u.uid, sc.amount, sc.data, sc.timestamp, s.tag " .
			"FROM `" . TABLE_PREFIX . "service_codes` AS sc " .
			"LEFT JOIN `" . TABLE_PREFIX . "services` AS s ON sc.service = s.id " .
			"LEFT JOIN `" . TABLE_PREFIX . "servers` AS srv ON sc.server = srv.id " .
			"LEFT JOIN `" . TABLE_PREFIX . "users` AS u ON sc.uid = u.uid " .
			"LEFT JOIN `" . TABLE_PREFIX . "pricelist` AS pl ON sc.tariff = pl.tariff AND sc.service = pl.service
			AND (pl.server = '-1' OR sc.server = pl.server) " .
			"LIMIT " . get_row_limit($G_PAGE)
		); // TODO
		$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

		$i = 0;
		$tbody = "";
		while ($row = $db->fetch_array_assoc($result)) {
			$i += 1;

			// Pobranie przycisku edycji oraz usuwania
			if (get_privilages("manage_service_codes"))
				$button_delete = create_dom_element("img", "", array(
					'id' => "delete_row_{$i}",
					'src' => "images/bin.png",
					'title' => $lang->delete . " " . $row['id']
				));
			else
				$button_delete = "";

			// Zabezpieczanie danych
			foreach ($row AS $key => $value)
				$row[$key] = htmlspecialchars($value);

			$row['amount'] = $row['amount'] ? $row['amount'] : $lang->none;
			$username = $row['uid'] ? $row['username'] . " ({$row['uid']})" : $lang->none;
			if ($row['tariff']) {
				$tariff = "({$row['tariff']})";
				if ($row['tariff_amount'])
					$tariff = $row['tariff_amount'] . " " . $row['tag'] . " " . $tariff;
			}
			else
				$tariff = $lang->none;

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

		// Dodajemy wszystkie skrypty
		foreach (scandir(SCRIPT_ROOT . "jscripts/admin/pages/service_codes") as $file)
			if (ends_at($file, ".js"))
				$scripts[] = $settings['shop_url_slash'] . "jscripts/admin/pages/service_codes/" . $file . "?version=" . VERSION;

		// Pobranie wygladu całej tabeli
		eval("\$output = \"" . get_template("admin/table_structure") . "\";");
		return $output;
	}

	public function get_action_box($box_id, $data)
	{
		global $heart, $lang;

		if (!get_privilages("manage_service_codes"))
			return array(
				'id' => "not_logged_in",
				'text' => $lang->not_logged_or_no_perm
			);

		switch ($box_id) {
			case "add_code":
				// Pobranie usług
				$services = "";
				foreach ($heart->get_services() as $id => $row) {
					if (($service_module = $heart->get_service_module($id)) === NULL || !object_implements($service_module, "IServiceAdminServiceCodes"))
						continue;

					$services .= create_dom_element("option", $row['name'], array(
						'value' => $row['id']
					));
				}

				eval("\$output = \"" . get_template("admin/action_boxes/service_code_add") . "\";");
				break;
		}

		return array(
			'id' => "ok",
			'template' => $output
		);
	}
}