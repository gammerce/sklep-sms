<?php

$heart->register_page("admin_antispam_questions", "PageAdminAntispamQuestions");

class PageAdminAntispamQuestions extends PageAdmin {

	protected $privilage = "view_antispam_questions";

	function __construct()
	{
		global $lang;
		$this->title = $lang['antispam_questions'];

		parent::__construct();

		global $settings, $scripts;
		$scripts[] = $settings['shop_url_slash'] . "jscripts/admin/antispam_questions.js?version=" . VERSION;
	}

	protected function content($get, $post) {
		global $db, $lang, $G_PAGE;

		// Pobranie taryf
		$result = $db->query(
			"SELECT SQL_CALC_FOUND_ROWS * " .
			"FROM `" . TABLE_PREFIX . "antispam_questions` " .
			"LIMIT " . get_row_limit($G_PAGE)
		);
		$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

		$i = 0;
		$tbody = "";
		while ($row = $db->fetch_array_assoc($result)) {
			$i += 1;
			// Pobranie przycisku edycji oraz usuwania

			if (get_privilages("manage_antispam_questions")) {
				$button_edit = create_dom_element("img", "", array(
					'id' => "edit_row_{$i}",
					'src' => "images/edit.png",
					'title' => "Edytuj {$row['tariff']}"
				));

				$button_delete = create_dom_element("img", "", array(
					'id' => "delete_row_{$i}",
					'src' => "images/bin.png",
					'title' => "Usuń {$row['tariff']}"
				));
			} else
				$button_delete = $button_edit = "";

			// Zabezpieczanie danych
			$row['answers'] = htmlspecialchars($row['answers']);

			// Pobranie danych do tabeli
			eval("\$tbody .= \"" . get_template("admin/antispam_questions_trow") . "\";");
		}

		// Nie ma zadnych danych do wyswietlenia
		if (!strlen($tbody))
			eval("\$tbody = \"" . get_template("admin/no_records") . "\";");

		if (get_privilages("manage_antispam_questions")) {
			// Pobranie przycisku dodającego taryfę
			$button = array(
				'id' => "button_add_antispam_question",
				'value' => $lang['add_antispam_question']
			);
			eval("\$buttons = \"" . get_template("admin/button") . "\";");
		}

		// Pobranie paginacji
		$pagination = get_pagination($rows_count, $G_PAGE, "admin.php", $get);
		if (strlen($pagination))
			$tfoot_class = "display_tfoot";

		// Pobranie nagłówka tabeli
		eval("\$thead = \"" . get_template("admin/antispam_questions_thead") . "\";");

		// Pobranie struktury tabeli
		eval("\$output = \"" . get_template("admin/table_structure") . "\";");
		return $output;
	}

}