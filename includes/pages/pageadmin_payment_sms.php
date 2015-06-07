<?php

$heart->register_page("payment_sms", "PageAdminPaymentSms", "admin");

class PageAdminPaymentSms extends PageAdmin {

	function __construct()
	{
		global $lang;
		$this->title = $lang['payment_sms'];

		parent::__construct();
	}

	protected function content($get, $post) {
		global $db, $settings, $lang, $G_PAGE;

		$where = "( t.payment = 'sms' ) ";

		// Wyszukujemy platnosci o konkretnym ID
		if (isset($get['payid'])) {
			if (strlen($where))
				$where .= " AND ";

			$where .= $db->prepare("( t.payment_id = '%s' ) ", array($get['payid']));

			// Podświetlenie konkretnej płatności
			//$row['class'] = "highlighted";
		} // Wyszukujemy dane ktore spelniaja kryteria
		else if (isset($get['search']))
			searchWhere(array("t.payment_id", "t.sms_text", "t.sms_code", "t.sms_number"), urldecode($get['search']), $where);

		// Jezeli jest jakis where, to dodajemy WHERE
		if (strlen($where))
			$where = "WHERE " . $where . " ";

		$result = $db->query(
			"SELECT SQL_CALC_FOUND_ROWS * " .
			"FROM ({$settings['transactions_query']}) as t " .
			$where .
			"ORDER BY t.timestamp DESC " .
			"LIMIT " . get_row_limit($G_PAGE)
		);
		$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

		$tbody = "";
		while ($row = $db->fetch_array_assoc($result)) {
			$row['free'] = $row['free'] ? strtoupper($lang['yes']) : strtoupper($lang['no']);
			$row['income'] = $row['income'] ? number_format($row['income'], 2) . " " . $settings['currency'] : "";
			$row['cost'] = $row['cost'] ? number_format($row['cost'], 2) . " " . $settings['currency'] : "";
			$row['platform'] = get_platform($row['platform']);

			// Pobranie danych do tabeli
			eval("\$tbody .= \"" . get_template("admin/payment_sms_trow") . "\";");
		}

		// Nie ma zadnych danych do wyswietlenia
		if (!strlen($tbody))
			eval("\$tbody = \"" . get_template("admin/no_records") . "\";");

		// Pole wyszukiwania
		$search_text = htmlspecialchars($get['search']);
		eval("\$buttons = \"" . get_template("admin/form_search") . "\";");

		// Pobranie paginacji
		$pagination = get_pagination($rows_count, $G_PAGE, "admin.php", $get);
		if (strlen($pagination))
			$tfoot_class = "display_tfoot";

		// Pobranie nagłówka tabeli
		eval("\$thead = \"" . get_template("admin/payment_sms_thead") . "\";");

		// Pobranie struktury tabeli
		eval("\$output = \"" . get_template("admin/table_structure") . "\";");
		return $output;
	}

}