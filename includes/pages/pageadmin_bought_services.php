<?php

use Admin\Table;
use Admin\Table\Wrapper;
use Admin\Table\Structure;
use Admin\Table\BodyRow;
use Admin\Table\Cell;
use Admin\Table\DOMElement;
use Admin\Table\Img;

$heart->register_page("bought_services", "PageAdminBoughtServices", "admin");

class PageAdminBoughtServices extends PageAdmin
{
	const PAGE_ID = "bought_services";

	function __construct()
	{
		global $lang;
		$this->title = $lang->translate('bought_services');

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

		$table->addHeadCell(new Cell($lang->translate('payment_admin')));
		$table->addHeadCell(new Cell($lang->translate('payment_id')));
		$table->addHeadCell(new Cell($lang->translate('user')));
		$table->addHeadCell(new Cell($lang->translate('server')));
		$table->addHeadCell(new Cell($lang->translate('service')));
		$table->addHeadCell(new Cell($lang->translate('amount')));
		$table->addHeadCell(new Cell("{$lang->translate('nick')}/{$lang->translate('ip')}/{$lang->translate('sid')}"));
		$table->addHeadCell(new Cell($lang->translate('additional')));
		$table->addHeadCell(new Cell($lang->translate('email')));
		$table->addHeadCell(new Cell($lang->translate('ip')));
		$table->addHeadCell(new Cell($lang->translate('date')));

		// Wyszukujemy dane ktore spelniaja kryteria
		$where = '';

		if (isset($get['search'])) {
			searchWhere(array("t.id", "t.payment", "t.payment_id", "t.uid", "t.ip", "t.email", "t.auth_data", "CAST(t.timestamp as CHAR)"), $get['search'], $where);
		}

		// Jezeli jest jakis where, to dodajemy WHERE
		if (strlen($where)) {
			$where = "WHERE " . $where . ' ';
		}

		$result = $db->query(
			"SELECT SQL_CALC_FOUND_ROWS * " .
			"FROM ({$settings['transactions_query']}) as t " .
			$where .
			"ORDER BY t.timestamp DESC " .
			"LIMIT " . get_row_limit($G_PAGE)
		);

		$table->setDbRowsAmount($db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()"));

		while ($row = $db->fetch_array_assoc($result)) {
			$body_row = new BodyRow();

			// Pobranie danych o usłudze, która została kupiona
			$service = $heart->get_service($row['service']);

			// Pobranie danych o serwerze na ktorym zostala wykupiona usługa
			$server = $heart->get_server($row['server']);

			$username = $row['uid'] ? htmlspecialchars($row['username']) . " ({$row['uid']})" : $lang->translate('none');

			// Przerobienie ilosci
			$amount = $row['amount'] != -1 ? $row['amount'] . ' ' . $service['tag'] : $lang->translate('forever');

			// Rozkulbaczenie extra daty
			$row['extra_data'] = json_decode($row['extra_data'], true);
			$extra_data = array();
			foreach ($row['extra_data'] as $key => $value) {
				if (!strlen($value)) {
					continue;
				}

				$value = htmlspecialchars($value);

				if ($key == "password") {
					$key = $lang->translate('password');
				} else {
					if ($key == "type") {
						$key = $lang->translate('type');
						$value = get_type_name($value);
					}
				}

				$extra_data[] = $key . ': ' . $value;
			}
			$extra_data = implode('<br />', $extra_data);

			// Pobranie linku płatności
			$payment_link = new DOMElement();
			$payment_link->setName('a');
			$payment_link->setParam('href', "admin.php?pid=payment_{$row['payment']}&payid={$row['payment_id']}");
			$payment_link->setParam('target', '_blank');

			$payment_img = new Img();
			$payment_img->setParam('src', 'images/go.png');
			$payment_img->setParam('title', $lang->translate('see_payment'));
			$payment_link->addContent($payment_img);

			$body_row->addAction($payment_link);

			$body_row->setDbId($row['id']);
			$body_row->addCell(new Cell($row['payment']));
			$body_row->addCell(new Cell($row['payment_id']));
			$body_row->addCell(new Cell($username));
			$body_row->addCell(new Cell($server['name']));
			$body_row->addCell(new Cell($service['name']));
			$body_row->addCell(new Cell($amount));
			$body_row->addCell(new Cell(htmlspecialchars($row['auth_data'])));
			$body_row->addCell(new Cell($extra_data));
			$body_row->addCell(new Cell(htmlspecialchars($row['email'])));
			$body_row->addCell(new Cell($row['ip']));

			$cell = new Cell(convertDate($row['timestamp']));
			$cell->setParam('headers', 'date');
			$body_row->addCell($cell);

			$table->addBodyRow($body_row);
		}

		$wrapper->setTable($table);

		return $wrapper->toHtml();
	}
}