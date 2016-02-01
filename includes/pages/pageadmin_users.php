<?php

use Admin\Table;
use Admin\Table\Wrapper;
use Admin\Table\Structure;
use Admin\Table\BodyRow;
use Admin\Table\Cell;
use Admin\Table\Img;

$heart->register_page("users", "PageAdminUsers", "admin");

class PageAdminUsers extends PageAdmin implements IPageAdmin_ActionBox
{

	const PAGE_ID = "users";
	protected $privilage = "view_users";

	function __construct()
	{
		global $lang;
		$this->title = $lang->translate('users');

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $heart, $db, $settings, $lang, $G_PAGE;

		$wrapper = new Wrapper();
		$wrapper->setTitle($this->title);
		$wrapper->setSearch();

		$table = new Structure();

		$cell = new Cell($lang->translate('id'));
		$cell->setParam('headers', 'id');
		$table->addHeadCell($cell);

		$table->addHeadCell(new Cell($lang->translate('username')));
		$table->addHeadCell(new Cell($lang->translate('firstname')));
		$table->addHeadCell(new Cell($lang->translate('surname')));
		$table->addHeadCell(new Cell($lang->translate('email')));
		$table->addHeadCell(new Cell($lang->translate('groups')));
		$table->addHeadCell(new Cell($lang->translate('wallet')));

		$where = '';
		if (isset($get['search'])) {
			searchWhere(array("`uid`", "`username`", "`forename`", "`surname`", "`email`", "`groups`", "`wallet`"), $get['search'], $where);
		}

		// Jezeli jest jakis where, to dodajemy WHERE
		if (strlen($where)) {
			$where = 'WHERE ' . $where . ' ';
		}

		$result = $db->query(
			"SELECT SQL_CALC_FOUND_ROWS `uid`, `username`, `forename`, `surname`, `email`, `groups`, `wallet` " .
			"FROM `" . TABLE_PREFIX . "users` " .
			$where .
			"LIMIT " . get_row_limit($G_PAGE)
		);

		$table->setDbRowsAmount($db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()"));

		while ($row = $db->fetch_array_assoc($result)) {
			$body_row = new BodyRow();

			$row['groups'] = explode(";", $row['groups']);
			$groups = array();
			foreach ($row['groups'] as $gid) {
				$group = $heart->get_group($gid);
				$groups[] = "{$group['name']} ({$group['id']})";
			}
			$groups = implode("; ", $groups);

			$body_row->setDbId($row['uid']);
			$body_row->addCell(new Cell(htmlspecialchars($row['username'])));
			$body_row->addCell(new Cell(htmlspecialchars($row['forename'])));
			$body_row->addCell(new Cell(htmlspecialchars($row['surname'])));
			$body_row->addCell(new Cell(htmlspecialchars($row['email'])));
			$body_row->addCell(new Cell($groups));

			$cell = new Cell(number_format($row['wallet'] / 100.0, 2) . ' ' . $settings['currency']);
			$cell->setParam('headers', 'wallet');
			$body_row->addCell($cell);

			$button_charge = new Img();
			$button_charge->setParam('class', 'charge_wallet');
			$button_charge->setParam('title', $lang->translate('charge') . ' ' . htmlspecialchars($row['username']));
			$button_charge->setParam('src', 'images/dollar.png');
			$body_row->addAction($button_charge);

			if (get_privilages('manage_users')) {
				$body_row->setButtonDelete(true);
				$body_row->setButtonEdit(true);
			}

			$table->addBodyRow($body_row);
		}

		$wrapper->setTable($table);

		return $wrapper->toHtml();
	}

	public function get_action_box($box_id, $data)
	{
		global $heart, $settings, $lang, $templates;

		if (!get_privilages("manage_users")) {
			return array(
				'status' => "not_logged_in",
				'text'   => $lang->translate('not_logged_or_no_perm')
			);
		}

		switch ($box_id) {
			case "user_edit":
				// Pobranie użytkownika
				$user = $heart->get_user($data['uid']);

				$groups = '';
				foreach ($heart->get_groups() as $group) {
					$groups .= create_dom_element("option", "{$group['name']} ( {$group['id']} )", array(
						'value'    => $group['id'],
						'selected' => in_array($group['id'], $user->getGroups()) ? "selected" : ""
					));
				}

				$output = eval($templates->render("admin/action_boxes/user_edit"));
				break;

			case "charge_wallet":
				$user = $heart->get_user($data['uid']);

				$output = eval($templates->render("admin/action_boxes/user_charge_wallet"));
				break;
		}

		return array(
			'status'   => 'ok',
			'template' => $output
		);
	}

}