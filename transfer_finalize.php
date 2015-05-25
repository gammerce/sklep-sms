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
if ($db->num_rows($result)) {
	die("OK");
}

// Decodujemy dane transakcji
$transaction_data = json_decode(base64_decode(urldecode($_POST['userdata'])), true);

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

	// Tworzymy obiekt usługi, którą będziemy dodawać
	$service_module = $heart->get_service_module($transaction_data['service']);

	// Dokonujemy zakupu usługi
	if ($service_module !== NULL) {
		$bought_service_id = $service_module->purchase(array(
			'user' => array(
				'uid' => $user['uid'],
				'name' => $user['username'],
				'ip' => $user['ip'],
				'email' => $user['email']
			),
			'transaction' => array(
				'method' => "transfer",
				'payment_id' => $_POST['orderid']
			),
			'order' => $transaction_data['order']
		));
	}

	if (isset($bought_service_id) && $bought_service_id !== FALSE) {
		log_info("Zaakceptowano płatność za usługę: {$bought_service_id} Kwota: {$_POST['amount']} ID transakcji: {$_POST['orderid']} Service: {$_POST['service']} {$user['username']}({$user['uid']})({$user['ip']})");
	} else {
		log_info("Płatność przelewem: {$_POST['orderid']} została zaakceptowana, jednakże moduł usługi {$transaction_data['service']} został źle zaprogramowany i nie doszło do zakupu.");
	}
} else {
	log_info("Nieudana autoryzacja transakcji: {$_POST['orderid']} Kwota: {$_POST['amount']} Service: {$_POST['service']} {$user['username']}({$user['uid']})({$user['ip']})");
}

output_page("OK");