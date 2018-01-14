<?php

use Admin\Table;
use Admin\Table\Wrapper;
use Admin\Table\Structure;
use Admin\Table\BodyRow;
use Admin\Table\Cell;
use Admin\Table\Div;

$heart->register_page("payment_wallet", "PageAdminPaymentWallet", "admin");

class PageAdminPaymentWallet extends PageAdmin
{
	const PAGE_ID = "payment_wallet";

	function __construct()
	{
		global $lang;
		$this->title = $lang->translate('payments_wallet');

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $db, $settings, $lang, $G_PAGE;

		$wrapper = new Wrapper();
		$wrapper->setTitle($this->title);

		$table = new Structure();

		$cell = new Cell($lang->translate('id'));
		$cell->setParam('headers', 'id');
		$table->addHeadCell($cell);

		$table->addHeadCell(new Cell($lang->translate('cost')));
		$table->addHeadCell(new Cell($lang->translate('ip')));

		$cell = new Cell($lang->translate('platform'));
		$cell->setParam('headers', 'platform');
		$table->addHeadCell($cell);

		$table->addHeadCell(new Cell($lang->translate('date')));

		$where = "";
		if (isset($get['payid'])) {
			$where .= $db->prepare(" AND `payment_id` = '%d' ", array($get['payid']));
		}

		$result = $db->query(
			"SELECT SQL_CALC_FOUND_ROWS * " .
			"FROM ({$settings['transactions_query']}) as t " .
			"WHERE t.payment = 'wallet' " . $where .
			"ORDER BY t.timestamp DESC " .
			"LIMIT " . get_row_limit($G_PAGE)
		);

		$table->setDbRowsAmount($db->get_column('SELECT FOUND_ROWS()', 'FOUND_ROWS()'));

		while ($row = $db->fetch_array_assoc($result)) {
			$body_row = new BodyRow();

			if ($get['highlight'] && $get['payid'] == $row['payment_id']) {
				$body_row->setParam('class', 'highlighted');
			}

			$cost = $row['cost'] ? number_format($row['cost'] / 100.0, 2) . " " . $settings['currency'] : "";

			$body_row->setDbId($row['payment_id']);
			$body_row->addCell(new Cell($cost));
			$body_row->addCell(new Cell(htmlspecialchars($row['ip'])));

			$cell = new Cell();
			$div = new Div(get_platform($row['platform']));
			$div->setParam('class', 'one_line');
			$cell->addContent($div);
			$body_row->addCell($cell);

			$body_row->addCell(new Cell(convertDate($row['timestamp'])));

			$table->addBodyRow($body_row);
		}

		$wrapper->setTable($table);

		return $wrapper->toHtml();
	}
}