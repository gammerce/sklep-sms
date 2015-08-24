<?php

/**
 * Created by MilyGosc.
 * URL: http://forum.sklep-sms.pl/showthread.php?tid=88
 */

if ($_SERVER['REMOTE_ADDR'] != '195.149.229.109' || empty($_POST))
	exit;

define('IN_SCRIPT', "1");
define("SCRIPT_NAME", "transfer_finalize_transferuj");

require_once "global.php";

$result = $db->query($db->prepare(
	"SELECT * FROM `" . TABLE_PREFIX . "payment_transfer` " .
	"WHERE `id` = '%d'",
	array($_POST['tr_id'])
));

// Próba ponownej autoryzacji
if ($db->num_rows($result))
	die('TRUE');

/** @var Entity_Purchase $purchase_data */
$purchase_data = unserialize(file_get_contents(SCRIPT_ROOT . "data/transfers/" . $_POST['tr_crc']));

$payment = new Payment("transferuj");

if ($_POST['tr_status'] == 'TRUE' && $_POST['tr_error'] == 'none') {
	// Dodanie informacji do bazy danych
	$db->query($db->prepare(
		"INSERT INTO `" . TABLE_PREFIX . "payment_transfer` " .
		"SET `id` = '%s', `income` = '%d', `transfer_service` = '%s', `ip` = '%s', `platform` = '%s' ",
		array($_POST['tr_id'], $purchase_data->getPayment('cost'), $_POST['id'], $purchase_data->user->getLastIp(), $purchase_data->user->getPlatform())
	));
	unlink(SCRIPT_ROOT . "data/transfers/" . $_POST['tr_crc']);

	// Dokonujemy zakupu usługi
	if (($service_module = $heart->get_service_module($purchase_data->getService())) !== NULL) {
		if (object_implements($service_module, "IService_Purchase")) {
			$purchase_data->setPayment(array(
				'method' => "transfer",
				'payment_id' => $_POST['tr_id']
			));
			$bought_service_id = $service_module->purchase($purchase_data);

			log_info($lang_shop->sprintf($lang_shop->payment_transfer_accepted, $bought_service_id, $_POST['tr_id'], $_POST['tr_amount'], $_POST['id'],
				$purchase_data->user->getUsername(), $purchase_data->user->getUid(), $purchase_data->user->getLastIp()));
		} else {
			log_info($lang_shop->sprintf($lang_shop->transfer_no_purchase, $_POST['tr_id'], $purchase_data->getService()));
		}
	} else {
		log_info($lang_shop->sprintf($lang_shop->transfer_bad_module, $_POST['tr_id'], $purchase_data->getService()));
	}
} else {
	log_info($lang_shop->sprintf($lang_shop->payment_not_accepted, $_POST['tr_id'], $_POST['tr_amount'], $_POST['id'],
		$purchase_data->user->getUsername(), $purchase_data->user->getUid(), $purchase_data->user->getLastIp()));
}

output_page('TRUE');