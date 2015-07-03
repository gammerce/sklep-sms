<?php

$heart->register_page("pricelist", "PageAdminPriceList", "admin");

class PageAdminPriceList extends PageAdmin
{

	protected $privilage = "manage_settings";

	function __construct()
	{
		global $lang;
		$this->title = $lang->pricelist;

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $heart, $db, $lang, $G_PAGE, $settings, $scripts;

		// Pobranie cen
		$result = $db->query(
			"SELECT SQL_CALC_FOUND_ROWS * " .
			"FROM `" . TABLE_PREFIX . "pricelist` " .
			"ORDER BY `service`, `server`, `tariff` " .
			"LIMIT " . get_row_limit($G_PAGE)
		);
		$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

		$i = 0;
		$tbody = "";
		while ($row = $db->fetch_array_assoc($result)) {
			$i += 1;
			// Pobranie przycisku edycji oraz usuwania
			$button_edit = create_dom_element("img", "", array(
				'id' => "edit_row_{$i}",
				'src' => "images/edit.png",
				'title' => $lang->edit . " " . $row['tariff']
			));
			$button_delete = create_dom_element("img", "", array(
				'id' => "delete_row_{$i}",
				'src' => "images/bin.png",
				'title' => $lang->delete . " " . $row['tariff']
			));

			if ($row['server'] != -1) {
				$temp_server = $heart->get_server($row['server']);
				$row['server'] = $temp_server['name'];
				unset($temp_server);
			} else
				$row['server'] = $lang->all_servers;

			$service = $heart->get_service($row['service']);

			// Pobranie danych do tabeli
			eval("\$tbody .= \"" . get_template("admin/pricelist_trow") . "\";");
		}

		// Nie ma zadnych danych do wyswietlenia
		if (!strlen($tbody))
			eval("\$tbody = \"" . get_template("admin/no_records") . "\";");

		// Pobranie przycisku dodającego cenę
		$buttons = create_dom_element("input", "", array(
			'id' => "button_add_price",
			'type' => "button",
			'value' => $lang->add_price
		));

		// Pobranie paginacji
		$pagination = get_pagination($rows_count, $G_PAGE, "admin.php", $get);
		if (strlen($pagination))
			$tfoot_class = "display_tfoot";

		// Pobranie nagłówka tabeli
		eval("\$thead = \"" . get_template("admin/pricelist_thead") . "\";");

		$scripts[] = $settings['shop_url_slash'] . "jscripts/admin/pricelist.js?version=" . VERSION;

		// Pobranie struktury tabeli
		eval("\$output = \"" . get_template("admin/table_structure") . "\";");
		return $output;
	}

}