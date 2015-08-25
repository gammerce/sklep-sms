<?php

$heart->register_page("antispam_questions", "PageAdminAntispamQuestions", "admin");

class PageAdminAntispamQuestions extends PageAdmin implements IPageAdmin_ActionBox
{

	const PAGE_ID = "antispam_questions";
	protected $privilage = "view_antispam_questions";

	function __construct()
	{
		global $lang;
		$this->title = $lang->antispam_questions;

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $db, $lang, $G_PAGE, $templates;

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
					'title' => $lang->edit . " " . $row['tariff']
				));

				$button_delete = create_dom_element("img", "", array(
					'id' => "delete_row_{$i}",
					'src' => "images/bin.png",
					'title' => $lang->delete . " " . $row['tariff']
				));
			} else
				$button_delete = $button_edit = "";

			// Zabezpieczanie danych
			$row['answers'] = htmlspecialchars($row['answers']);

			// Pobranie danych do tabeli
			$tbody .= eval($templates->render("admin/antispam_questions_trow"));
		}

		// Nie ma zadnych danych do wyswietlenia
		if (!strlen($tbody))
			$tbody = eval($templates->render("admin/no_records"));

		if (get_privilages("manage_antispam_questions"))
			// Pobranie przycisku dodającego pytanie antyspamowe
			$buttons = create_dom_element("input", "", array(
				'id' => "antispam_question_button_add",
				'type' => "button",
				'value' => $lang->add_antispam_question
			));

		// Pobranie paginacji
		$pagination = get_pagination($rows_count, $G_PAGE, "admin.php", $get);
		if (strlen($pagination))
			$tfoot_class = "display_tfoot";

		// Pobranie nagłówka tabeli
		$thead = eval($templates->render("admin/antispam_questions_thead"));

		// Pobranie struktury tabeli
		$output = eval($templates->render("admin/table_structure"));
		return $output;
	}

	public function get_action_box($box_id, $data)
	{
		global $db, $lang, $templates;

		if (!get_privilages("manage_antispam_questions"))
			return array(
				'status' => "not_logged_in",
				'text' => $lang->not_logged_or_no_perm
			);

		switch ($box_id) {
			case "antispam_question_add":
				$output = eval($templates->render("admin/action_boxes/antispam_question_add"));
				break;

			case "antispam_question_edit":
				$row = $db->fetch_array_assoc($db->query($db->prepare(
					"SELECT * FROM `" . TABLE_PREFIX . "antispam_questions` " .
					"WHERE `id` = '%d'",
					array($data['id'])
				)));
				$row['question'] = htmlspecialchars($row['question']);
				$row['answers'] = htmlspecialchars($row['answers']);

				$output = eval($templates->render("admin/action_boxes/antispam_question_edit"));
				break;
		}

		return array(
			'status' => 'ok',
			'template' => $output
		);
	}

}