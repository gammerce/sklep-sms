<?php

$heart->register_page("payment_transfer", "PageAdminPaymentTransfer", "admin");

class PageAdminPaymentTransfer extends PageAdmin
{

	const PAGE_ID = "payment_transfer";

	function __construct()
	{
		global $lang;
		$this->title = $lang->payments_transfer;

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $db, $settings, $lang, $G_PAGE, $templates;

		$where = "( t.payment = 'transfer' ) ";

		// Wyszukujemy dane ktore spelniaja kryteria
		if (isset($get['search']))
			searchWhere(array("t.payment_id", "t.income", "t.ip"), $get['search'], $where);

		if (isset($get['payid']))
			$where .= $db->prepare(" AND `payment_id` = '%s' ", array($get['payid']));

		// Jezeli jest jakis where, to dodajemy WHERE
		if (strlen($where))
			$where = "WHERE " . $where . " ";

		// Wykonujemy zapytanie
		$result = $db->query(
			"SELECT SQL_CALC_FOUND_ROWS * " .
			"FROM ({$settings['transactions_query']}) as t " .
			$where .
			"ORDER BY t.timestamp DESC " .
			"LIMIT " . get_row_limit($G_PAGE)
		);
		$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

		// Pobieramy dane
		$tbody = "";
		while ($row = $db->fetch_array_assoc($result)) {
			$row['income'] = $row['income'] ? number_format($row['income'] / 100.0, 2) . " " . $settings['currency'] : "";

			// Podświetlenie konkretnej płatności
			if ($get['highlight'] && $get['payid'] == $row['payment_id'])
				$row['class'] = "highlighted";

			$row['platform'] = get_platform($row['platform']);

			// Poprawienie timestampa
			$row['timestamp'] = convertDate($row['timestamp']);

			// Pobranie danych do tabeli
			$tbody .= eval($templates->render("admin/payment_transfer_trow"));
		}

		// Nie ma zadnych danych do wyswietlenia
		if (!strlen($tbody))
			$tbody = eval($templates->render("admin/no_records"));

		// Pole wyszukiwania
		$search_text = htmlspecialchars($get['search']);
		$buttons = eval($templates->render("admin/form_search"));

		// Pobranie paginacji
		$pagination = get_pagination($rows_count, $G_PAGE, "admin.php", $get);
		if (strlen($pagination))
			$tfoot_class = "display_tfoot";

		// Pobranie nagłówka tabeli
		$thead = eval($templates->render("admin/payment_transfer_thead"));

		// Pobranie struktury tabeli
		$output = eval($templates->render("admin/table_structure"));
		return $output;
	}

}