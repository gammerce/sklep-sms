<?php

use Admin\Table;
use Admin\Table\Wrapper;
use Admin\Table\Structure;
use Admin\Table\BodyRow;
use Admin\Table\Cell;

$heart->register_page("transaction_services", "PageAdminTransactionServices", "admin");

class PageAdminTransactionServices extends PageAdmin implements IPageAdmin_ActionBox
{

	const PAGE_ID = "transaction_services";
	protected $privilage = "manage_settings";

	function __construct()
	{
		global $lang;
		$this->title = $lang->translate('transaction_services');

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

		$table->addHeadCell(new Cell($lang->translate('name')));
		$table->addHeadCell(new Cell($lang->translate('sms_service')));
		$table->addHeadCell(new Cell($lang->translate('transfer_service')));

		$result = $db->query(
			"SELECT SQL_CALC_FOUND_ROWS * FROM `" . TABLE_PREFIX . "transaction_services` " .
			"LIMIT " . get_row_limit($G_PAGE)
		);

		$table->setDbRowsAmount($db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()"));

		while ($row = $db->fetch_array_assoc($result)) {
			$body_row = new BodyRow();

			$sms_service = $row['sms'] ? $lang->strtoupper($lang->translate('yes')) : $lang->strtoupper($lang->translate('no'));
			$transfer_service = $row['transfer'] ? $lang->strtoupper($lang->translate('yes')) : $lang->strtoupper($lang->translate('no'));

			$body_row->setDbId($row['id']);
			$body_row->addCell(new Cell($row['name']));
			$body_row->addCell(new Cell($sms_service));
			$body_row->addCell(new Cell($transfer_service));

			$body_row->setButtonEdit(true);

			$table->addBodyRow($body_row);
		}

		$wrapper->setTable($table);

		return $wrapper->toHtml();
	}

	public function get_action_box($box_id, $data)
	{
		global $db, $lang, $templates;

		if (!get_privilages("manage_settings"))
			return array(
				'status' => "not_logged_in",
				'text' => $lang->translate('not_logged_or_no_perm')
			);

		switch ($box_id) {
			case "transaction_service_edit":
				// Pobranie danych o metodzie płatności
				$result = $db->query($db->prepare(
					"SELECT * FROM `" . TABLE_PREFIX . "transaction_services` " .
					"WHERE `id` = '%s'",
					array($data['id'])
				));
				$transaction_service = $db->fetch_array_assoc($result);

				$transaction_service['id'] = htmlspecialchars($transaction_service['id']);
				$transaction_service['name'] = htmlspecialchars($transaction_service['name']);
				$transaction_service['data'] = json_decode($transaction_service['data']);

				$data_values = "";
				foreach ($transaction_service['data'] as $name => $value) {
					switch ($name) {
						case 'sms_text':
							$text = $lang->strtoupper($lang->translate('sms_code'));
							break;
						case 'account_id':
							$text = $lang->strtoupper($lang->translate('account_id'));
							break;
						default:
							$text = $lang->strtoupper($name);
							break;
					}
					$data_values .= eval($templates->render("tr_name_input"));
				}

				$output = eval($templates->render("admin/action_boxes/transaction_service_edit"));
				break;
		}

		return array(
			'status' => 'ok',
			'template' => $output
		);
	}

}