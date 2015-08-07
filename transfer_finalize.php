<?php

define('IN_SCRIPT', "1");
define("SCRIPT_NAME", "transfer_finalize");

require_once "global.php";

$result = $db->query($db->prepare(
	"SELECT * FROM `" . TABLE_PREFIX . "payment_transfer` " .
	"WHERE `id` = '%d'",
	array($_POST['orderid'])
));

// Próba ponownej autoryzacji
if ($db->num_rows($result))
	die("OK");

// Decodujemy dane transakcji
$transaction_data = json_decode(file_get_contents(SCRIPT_ROOT . "data/transfers/" . $_POST['userdata']), true);

// Pobieramy dane użytkownika
if ($transaction_data['uid'])
	$user = $heart->get_user($transaction_data['uid']);

$user['ip'] = $transaction_data['ip'];
$user['platform'] = $transaction_data['platform'];
$user['email'] = $transaction_data['email'];

$payment = new Payment("cashbill");

if ($payment->payment_api->check_sign($_POST, $payment->payment_api->data['key'], $_POST['sign']) && strtoupper($_POST['status']) == 'OK' && $_POST['service'] == $payment->payment_api->data['service']) {
	// Dodanie informacji do bazy danych
	$db->query($db->prepare(
		"INSERT INTO `" . TABLE_PREFIX . "payment_transfer` " .
		"SET `id` = '%s', `income` = '%.2f', `transfer_service` = '%s', `ip` = '%s', `platform` = '%s' ",
		array($_POST['orderid'], $_POST['amount'], $_POST['service'], $user['ip'], $user['platform'])
	));
	unlink(SCRIPT_ROOT . "data/transfers/" . $_POST['userdata']);

	// Dokonujemy zakupu usługi
	if (($service_module = $heart->get_service_module($transaction_data['service'])) !== NULL) {
		if (object_implements($service_module, "IService_Purchase")) {
			$purchase_data = new Entity_Purchase();
			$purchase_data->user = $user;
			$purchase_data->setPayment(array(
				'method' => "transfer",
				'payment_id' => $_POST['orderid']
			));
			$purchase_data->setOrder($transaction_data['order']);
			$purchase_data->setEmail($user['email']);
			$bought_service_id = $service_module->purchase($purchase_data);

			log_info($lang_shop->sprintf($lang_shop->payment_accepted, $bought_service_id, $_POST['amount'],
				$_POST['orderid'], $_POST['service'], $_POST['service'], $user['username'], $user['uid'], $user['ip']));
		} else {
			log_info($lang_shop->sprintf($lang_shop->transfer_no_purchase, $_POST['orderid'], $transaction_data['service']));
		}
	} else
		log_info($lang_shop->sprintf($lang_shop->transfer_bad_module, $_POST['orderid'], $transaction_data['service']));
} else
	log_info($lang_shop->sprintf($lang_shop->payment_not_accepted, $_POST['orderid'], $_POST['amount'], $_POST['service'], $user['username'], $user['uid'], $user['ip']));

output_page("OK");