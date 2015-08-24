<?php

define('IN_SCRIPT', "1");
define("SCRIPT_NAME", "transfer_finalize");

require_once 'global.php';

$result = $db->query($db->prepare(
	"SELECT * FROM `" . TABLE_PREFIX . "payment_transfer` " .
	"WHERE `id` = '%d'",
	array($_POST['orderid'])
));

// Próba ponownej autoryzacji
if ($db->num_rows($result))
	die('OK');

/** @var Entity_Purchase $purchase_data */
$purchase_data = unserialize(file_get_contents(SCRIPT_ROOT . "data/transfers/" . $_POST['userdata']));

$payment = new Payment("cashbill");

if ($payment->payment_api->check_sign($_POST, $payment->payment_api->data['key'], $_POST['sign']) && strtoupper($_POST['status']) == 'OK' && $_POST['service'] == $payment->payment_api->data['service']) {
	// Dodanie informacji do bazy danych
	$db->query($db->prepare(
		"INSERT INTO `" . TABLE_PREFIX . "payment_transfer` " .
		"SET `id` = '%s', `income` = '%d', `transfer_service` = '%s', `ip` = '%s', `platform` = '%s' ",
		array($_POST['orderid'], $purchase_data->getPayment('cost'), $_POST['service'], $purchase_data->user->getLastIp(), $purchase_data->user->getPlatform())
	));
	unlink(SCRIPT_ROOT . "data/transfers/" . $_POST['userdata']);

	// Dokonujemy zakupu usługi
	if (($service_module = $heart->get_service_module($purchase_data->getService())) !== NULL) {
		if (object_implements($service_module, "IService_Purchase")) {
			$purchase_data->setPayment(array(
				'method' => 'transfer',
				'payment_id' => $_POST['orderid']
			));
			$bought_service_id = $service_module->purchase($purchase_data);

			log_info($lang_shop->sprintf($lang_shop->payment_transfer_accepted, $bought_service_id, $_POST['orderid'], $_POST['amount'],
				$_POST['service'], $purchase_data->user->getUsername(), $purchase_data->user->getUid(), $purchase_data->user->getLastIp()));
		} else {
			log_info($lang_shop->sprintf($lang_shop->transfer_no_purchase, $_POST['orderid'], $purchase_data->getService()));
		}
	} else {
		log_info($lang_shop->sprintf($lang_shop->transfer_bad_module, $_POST['orderid'], $purchase_data->getService()));
	}
} else {
	log_info($lang_shop->sprintf($lang_shop->payment_not_accepted, $_POST['orderid'], $_POST['amount'], $_POST['service'],
		$purchase_data->user->getUsername(), $purchase_data->user->getUid(), $purchase_data->user->getLastIp()));
}

output_page('OK');