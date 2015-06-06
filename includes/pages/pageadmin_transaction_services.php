<?php

$heart->register_page("admin_transaction_services", "PageAdminTransactionServices");

class PageAdminTransactionServices extends PageAdmin {

	protected $privilage = "manage_settings";

	function __construct()
	{
		global $lang;
		$this->title = $lang['transaction_services'];

		parent::__construct();

		global $settings, $scripts;
		$scripts[] = $settings['shop_url_slash'] . "jscripts/admin/transaction_services.js?version=" . VERSION;
	}

	protected function content($get, $post) {
		global $db, $lang, $G_PAGE;

		// Pobranie listy serwisów transakcyjnych
		$result = $db->query(
			"SELECT SQL_CALC_FOUND_ROWS * " .
			"FROM `" . TABLE_PREFIX . "transaction_services` " .
			"LIMIT " . get_row_limit($G_PAGE)
		);
		$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

		$i = 0;
		$tbody = "";
		while ($row = $db->fetch_array_assoc($result)) {
			$i += 1;
			$row['sms'] = $row['sms'] ? "TAK" : "NIE";
			$row['transfer'] = $row['transfer'] ? "TAK" : "NIE";

			// Pobranie przycisku edycji
			$button_edit = create_dom_element("img", "", array(
				'id' => "edit_row_{$i}",
				'src' => "images/edit.png",
				'title' => "Edytuj " . $row['name']
			));

			// Pobranie danych do tabeli
			eval("\$tbody .= \"" . get_template("admin/transaction_services_trow") . "\";");
		}

		// Nie ma zadnych danych do wyswietlenia
		if (!strlen($tbody))
			eval("\$tbody = \"" . get_template("admin/no_records") . "\";");

		// Pobranie paginacji
		$pagination = get_pagination($rows_count, $G_PAGE, "admin.php", $get);
		if (strlen($pagination))
			$tfoot_class = "display_tfoot";

		// Pobranie nagłówka tabeli
		eval("\$thead = \"" . get_template("admin/transaction_services_thead") . "\";");

		// Pobranie struktury tabeli
		eval("\$output = \"" . get_template("admin/table_structure") . "\";");
		return $output;
	}

}