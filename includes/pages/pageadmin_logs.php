<?php

$heart->register_page("logs", "PageAdminLogs", "admin");

class PageAdminLogs extends PageAdmin
{

	const PAGE_ID = "logs";
	protected $privilage = "view_logs";

	function __construct()
	{
		global $lang;
		$this->title = $lang->logs;

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $db, $lang, $G_PAGE, $templates;

		// Wyszukujemy dane ktore spelniaja kryteria
		if (isset($get['search']))
			searchWhere(array("`id`", "`text`", "CAST(`timestamp` as CHAR)"), urldecode($get['search']), $where);

		// Jezeli jest jakis where, to dodajemy WHERE
		if (strlen($where))
			$where = "WHERE " . $where . " ";

		// Pobranie logów
		$result = $db->query(
			"SELECT SQL_CALC_FOUND_ROWS * FROM `" . TABLE_PREFIX . "logs` " .
			$where .
			"ORDER BY `id` DESC " .
			"LIMIT " . get_row_limit($G_PAGE)
		);
		$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

		$i = 0;
		$tbody = "";
		while ($row = $db->fetch_array_assoc($result)) {
			$i += 1;
			// Pobranie przycisku usuwania
			if (get_privilages("manage_logs"))
				$button_delete = create_dom_element("img", "", array(
					'id' => "delete_row_{$i}",
					'src' => "images/bin.png",
					'title' => $lang->delete . " " . $row['id']
				));
			else
				$button_delete = "";

			// Zabezpieczanie danych
			$row['text'] = htmlspecialchars($row['text']);

			// Pobranie danych do tabeli
			$tbody .= eval($templates->render("admin/logs_trow"));
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
		$thead = eval($templates->render("admin/logs_thead"));

		// Pobranie wygladu całej tabeli
		$output = eval($templates->render("admin/table_structure"));
		return $output;
	}

}