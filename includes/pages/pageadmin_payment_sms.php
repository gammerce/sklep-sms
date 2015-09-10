<?php

use Admin\Table;
use Admin\Table\Wrapper;
use Admin\Table\Structure;
use Admin\Table\BodyRow;
use Admin\Table\Cell;
use Admin\Table\Div;

$heart->register_page("payment_sms", "PageAdminPaymentSms", "admin");

class PageAdminPaymentSms extends PageAdmin
{

	const PAGE_ID = "payment_sms";

	function __construct()
	{
		global $lang;
		$this->title = $lang->payments_sms;

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $db, $settings, $lang, $G_PAGE;

		$wrapper = new Wrapper();
		$wrapper->setTitle($this->title);

		$table = new Structure();

		$cell = new Cell($lang->id);
		$cell->setParam('headers', 'id');
		$table->addHeadCell($cell);

		$table->addHeadCell(new Cell($lang->content));
		$table->addHeadCell(new Cell($lang->number));
		$table->addHeadCell(new Cell($lang->sms['return_code']));
		$table->addHeadCell(new Cell($lang->income));
		$table->addHeadCell(new Cell($lang->cost));
		$table->addHeadCell(new Cell($lang->free_of_charge));
		$table->addHeadCell(new Cell($lang->ip));

		$cell = new Cell($lang->platform);
		$cell->setParam('headers', 'platform');
		$table->addHeadCell($cell);

		$table->addHeadCell(new Cell($lang->date));

		$where = "( t.payment = 'sms' ) ";

		// Wyszukujemy platnosci o konkretnym ID
		if (isset($get['payid'])) {
			if (strlen($where))
				$where .= " AND ";

			$where .= $db->prepare("( t.payment_id = '%s' ) ", array($get['payid']));

			// Podświetlenie konkretnej płatności
			//$row['class'] = "highlighted";
		} // Wyszukujemy dane ktore spelniaja kryteria
		else if (isset($get['search'])) {
			searchWhere(array("t.payment_id", "t.sms_text", "t.sms_code", "t.sms_number"), $get['search'], $where);
		}

		if (isset($get['payid'])) {
			$where .= $db->prepare(" AND `payment_id` = '%d' ", array($get['payid']));
		}

		// Jezeli jest jakis where, to dodajemy WHERE
		if (strlen($where)) {
			$where = "WHERE " . $where . " ";
		}

		$result = $db->query(
			"SELECT SQL_CALC_FOUND_ROWS * " .
			"FROM ({$settings['transactions_query']}) as t " .
			$where .
			"ORDER BY t.timestamp DESC " .
			"LIMIT " . get_row_limit($G_PAGE)
		);

		$table->setDbRowsAmount($db->get_column('SELECT FOUND_ROWS()', 'FOUND_ROWS()'));

		while ($row = $db->fetch_array_assoc($result)) {
			$body_row = new BodyRow();

			if ($get['highlight'] && $get['payid'] == $row['payment_id']) {
				$body_row->setParam('class', 'highlighted');
			}

			$free = $row['free'] ? $lang->strtoupper($lang->yes) : $lang->strtoupper($lang->no);
			$income = $row['income'] ? number_format($row['income'] / 100.0, 2) . " " . $settings['currency'] : "";
			$cost = $row['cost'] ? number_format($row['cost'] / 100.0, 2) . " " . $settings['currency'] : "";

			$body_row->setDbId($row['payment_id']);
			$body_row->addCell(new Cell($row['sms_text']));
			$body_row->addCell(new Cell($row['sms_number']));
			$body_row->addCell(new Cell($row['sms_code']));
			$body_row->addCell(new Cell($income));
			$body_row->addCell(new Cell($cost));
			$body_row->addCell(new Cell($free));
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