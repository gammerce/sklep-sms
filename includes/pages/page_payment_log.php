<?php

$heart->register_page("payment_log", "PagePaymentLog");

class PagePaymentLog extends Page
{

	protected $require_login = 1;

	function __construct()
	{
		global $lang;
		$this->title = $lang->payment_log;

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $heart, $db, $settings, $user, $lang, $G_PAGE, $stylesheets;

		$result = $db->query($db->prepare(
			"SELECT SQL_CALC_FOUND_ROWS * FROM ({$settings['transactions_query']}) as t " .
			"WHERE t.uid = '%d' " .
			"ORDER BY t.timestamp DESC " .
			"LIMIT " . get_row_limit($G_PAGE, 10),
			array($user['uid'])
		));
		$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

		$payment_logs = "";
		while ($row = $db->fetch_array_assoc($result)) {
			$date = htmlspecialchars($row['timestamp']);
			$cost = number_format(floatval($row['cost']), 2) . " " . $settings['currency'];

			if (($service_module = $heart->get_service_module($row['service'])) !== NULL && class_has_interface($service_module, "IServicePurchaseWeb")) {
				$log_info = $service_module->purchase_info("payment_log", $row);
				$desc = $log_info['text'];
				$class = $log_info['class'];
			} else {
				$temp_service = $heart->get_service($row['service']);
				$temp_server = $heart->get_server($row['server']);
				$desc = $lang->sprintf($lang->service_was_bought, $temp_service['name'], $temp_server['name']);
				$class = "outcome";
				unset($temp_service);
				unset($temp_server);
			}

			$row['auth_data'] = htmlspecialchars($row['auth_data']);
			$row['email'] = htmlspecialchars($row['email']);

			eval("\$payment_log_brick = \"" . get_template("payment_log_brick") . "\";");
			$payment_logs .= create_dom_element("div", $payment_log_brick, $data = array(
				'class' => "brick " . $class
			));
		}

		$pagination = get_pagination($rows_count, $G_PAGE, "index.php", $get, 10);
		$pagination_class = strlen($pagination) ? "" : "display_none";

		eval("\$output = \"" . get_template("payment_log") . "\";");

		$stylesheets[] = $settings['shop_url_slash'] . "styles/style_payment_log.css?version=" . VERSION;

		return $output;
	}

}