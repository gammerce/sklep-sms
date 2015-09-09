<?php

use Admin\Table;
use Admin\Table\Wrapper;
use Admin\Table\Structure;
use Admin\Table\BodyRow;
use Admin\Table\Cell;
use Admin\Table\Input;

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
		global $db, $lang, $G_PAGE;

		$wrapper = new Wrapper();
		$wrapper->setTitle($this->title);

		$table = new Structure();

		$cell = new Cell($lang->id);
		$cell->setParam('headers', 'id');
		$table->addHeadCell($cell);

		$table->addHeadCell(new Cell($lang->question));
		$table->addHeadCell(new Cell($lang->answers));

		$result = $db->query(
			"SELECT SQL_CALC_FOUND_ROWS * " .
			"FROM `" . TABLE_PREFIX . "antispam_questions` " .
			"LIMIT " . get_row_limit($G_PAGE)
		);

		$table->setDbRowsAmount($db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()"));

		while ($row = $db->fetch_array_assoc($result)) {
			$body_row = new BodyRow();

			$body_row->setDbId($row['id']);
			$body_row->addCell(new Cell($row['question']));
			$body_row->addCell(new Cell($row['answers']));
			if (get_privilages("manage_antispam_questions")) {
				$body_row->setButtonDelete(true);
				$body_row->setButtonEdit(true);
			}

			$table->addBodyRow($body_row);
		}

		$wrapper->setTable($table);

		if (get_privilages("manage_antispam_questions")) {
			$button = new Input();
			$button->setParam('id', 'antispam_question_button_add');
			$button->setParam('type', 'button');
			$button->setParam('value', $lang->add_antispam_question);
			$wrapper->addButton($button);
		}

		return $wrapper->toHtml();
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