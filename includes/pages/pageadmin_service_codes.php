<?php

use Admin\Table;
use Admin\Table\Wrapper;
use Admin\Table\Structure;
use Admin\Table\BodyRow;
use Admin\Table\Cell;
use Admin\Table\Input;

$heart->register_page("service_codes", "PageAdminServiceCodes", "admin");

class PageAdminServiceCodes extends PageAdmin implements IPageAdmin_ActionBox
{

	const PAGE_ID = "service_codes";
	protected $privilage = "view_service_codes";

	function __construct()
	{
		global $lang;
		$this->title = $lang->translate('service_codes');

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $db, $lang, $G_PAGE;

		$wrapper = new Wrapper();
		$wrapper->setTitle($this->title);

		$table = new Structure();

		$cell = new Cell($lang->translate('id'));
		$cell->setParam('headers', 'id');
		$table->addHeadCell($cell);

		$table->addHeadCell(new Cell($lang->translate('code')));
		$table->addHeadCell(new Cell($lang->translate('service')));
		$table->addHeadCell(new Cell($lang->translate('server')));
		$table->addHeadCell(new Cell($lang->translate('amount')));
		$table->addHeadCell(new Cell($lang->translate('user')));
		$table->addHeadCell(new Cell($lang->translate('date_of_creation')));

		$result = $db->query(
			"SELECT SQL_CALC_FOUND_ROWS *, sc.id, sc.code, s.name AS `service`, srv.name AS `server`, sc.tariff, pl.amount AS `tariff_amount`,
			u.username, u.uid, sc.amount, sc.data, sc.timestamp, s.tag " .
			"FROM `" . TABLE_PREFIX . "service_codes` AS sc " .
			"LEFT JOIN `" . TABLE_PREFIX . "services` AS s ON sc.service = s.id " .
			"LEFT JOIN `" . TABLE_PREFIX . "servers` AS srv ON sc.server = srv.id " .
			"LEFT JOIN `" . TABLE_PREFIX . "users` AS u ON sc.uid = u.uid " .
			"LEFT JOIN `" . TABLE_PREFIX . "pricelist` AS pl ON sc.tariff = pl.tariff AND sc.service = pl.service
			AND (pl.server = '-1' OR sc.server = pl.server) " .
			"LIMIT " . get_row_limit($G_PAGE)
		);

		$table->setDbRowsAmount($db->get_column('SELECT FOUND_ROWS()', 'FOUND_ROWS()'));

		while ($row = $db->fetch_array_assoc($result)) {
			$body_row = new BodyRow();

			$username = $row['uid'] ? $row['username'] . " ({$row['uid']})" : $lang->translate('none');

			if ($row['tariff_amount']) {
				$amount = $row['tariff_amount'] . ' ' . $row['tag'];
			}
			else if ($row['tariff']) {
				$amount = $lang->translate('tariff') . ': ' . $row['tariff'];
			}
			else if ($row['amount']) {
				$amount = $row['amount'];
			}
			else {
				$amount = $lang->translate('none');
			}

			$body_row->setDbId($row['id']);
			$body_row->addCell(new Cell(htmlspecialchars($row['code'])));
			$body_row->addCell(new Cell(htmlspecialchars($row['service'])));
			$body_row->addCell(new Cell(htmlspecialchars($row['server'])));
			$body_row->addCell(new Cell($amount));
			$body_row->addCell(new Cell($username));
			$body_row->addCell(new Cell(convertDate($row['timestamp'])));

			if (get_privilages('manage_service_codes')) {
				$body_row->setButtonDelete(true);
			}

			$table->addBodyRow($body_row);
		}

		$wrapper->setTable($table);

		if (get_privilages('manage_service_codes')) {
			$button = new Input();
			$button->setParam('id', 'service_code_button_add');
			$button->setParam('type', 'button');
			$button->setParam('value', $lang->translate('add_code'));
			$wrapper->addButton($button);
		}

		return $wrapper->toHtml();
	}

	public function get_action_box($box_id, $data)
	{
		global $heart, $lang, $templates;

		if (!get_privilages("manage_service_codes"))
			return array(
				'status' => "not_logged_in",
				'text' => $lang->translate('not_logged_or_no_perm')
			);

		switch ($box_id) {
			case "code_add":
				// Pobranie usług
				$services = "";
				foreach ($heart->get_services() as $id => $row) {
					if (($service_module = $heart->get_service_module($id)) === NULL || !object_implements($service_module, "IService_ServiceCodeAdminManage"))
						continue;

					$services .= create_dom_element("option", $row['name'], array(
						'value' => $row['id']
					));
				}

				$output = eval($templates->render("admin/action_boxes/service_code_add"));
				break;
		}

		return array(
			'status' => 'ok',
			'template' => $output
		);
	}
}