<?php

$heart->register_page("players_flags", "PageAdminPlayersFlags", "admin");

class PageAdminPlayersFlags extends PageAdmin
{

	const PAGE_ID = "players_flags";
	protected $privilage = "view_player_flags";
	private $flags = "abcdefghijklmnopqrstuyvwxz";

	function __construct()
	{
		global $lang;
		$this->title = $lang->players_flags;

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $heart, $db, $settings, $lang, $G_PAGE;

		$result = $db->query(
			"SELECT SQL_CALC_FOUND_ROWS * FROM `" . TABLE_PREFIX . "players_flags` " .
			"ORDER BY `id` DESC " .
			"LIMIT " . get_row_limit($G_PAGE)
		);
		$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

		$i = 0;
		$tbody = "";
		while ($row = $db->fetch_array_assoc($result)) {
			$i += 1;
			// Zabezpieczanie danych
			$row['auth_data'] = htmlspecialchars($row['auth_data']);

			// Zamiana dat
			for ($j = 0; $j < strlen($this->flags); ++$j)
				if (!$row[$this->flags[$j]])
					$row[$this->flags[$j]] = " ";
				else if ($row[$this->flags[$j]] == -1)
					$row[$this->flags[$j]] = $lang->never;
				else
					$row[$this->flags[$j]] = date($settings['date_format'], $row[$this->flags[$j]]);

			// Pobranie danych serwera
			$temp_server = $heart->get_server($row['server']);
			$row['server'] = $temp_server['name'];
			unset($temp_server);

			// Pobranie danych do tabeli
			eval("\$tbody .= \"" . get_template("admin/players_flags_trow") . "\";");
		}

		// Nie ma zadnych danych do wyswietlenia
		if (!strlen($tbody))
			eval("\$tbody = \"" . get_template("admin/no_records") . "\";");

		// Pobranie paginacji
		$pagination = get_pagination($rows_count, $G_PAGE, "admin.php", $get);
		if (strlen($pagination))
			$tfoot_class = "display_tfoot";

		// Pobranie nagłówka tabeli
		eval("\$thead = \"" . get_template("admin/players_flags_thead") . "\";");

		// Pobranie struktury tabeli
		eval("\$output = \"" . get_template("admin/table_structure") . "\";");
		return $output;
	}

}