<?php

define('IN_SCRIPT', "1");
define("SCRIPT_NAME", "servers_stuff");

header("Content-type: text/plain; charset=\"UTF-8\"");

require_once "global.php";
require_once SCRIPT_ROOT . "includes/functions_jsonhttp.php";

function xml_output($return_value, $text, $positive, $extra_data = "")
{
	$output = "<return_value>{$return_value}</return_value>";
	$output .= "<text>{$text}</text>";
	$output .= "<positive>{$positive}</positive>";
	$output .= $extra_data;
	output_page($output, "Content-type: text/plain; charset=\"UTF-8\"");
}


// Musi byc podany hash random_keya
if ($_GET['key'] != md5($settings['random_key'])) {
	exit;
}

$action = $_GET['action'];

if ($action == "purchase_service") {
	$output = "";
	$service_module = $heart->get_service_module(urldecode($_GET['service']));

	if ($service_module === NULL)
		xml_output("bad_module", $lang['module_is_bad'], 0);

	// Sprawdzamy dane zakupu
	$return_validation = $service_module->validate_purchase_data(array(
		'user' => array(
			'uid' => $_GET['uid'],
			'ip' => urldecode($_GET['ip']),
			'platform' => urldecode($_GET['platform'])
		),
		'order' => array(
			'server' => $_GET['server'],
			'type' => $_GET['type'],
			'auth_data' => urldecode($_GET['auth_data']),
			'password' => urldecode($_GET['password']),
			'passwordr' => urldecode($_GET['password'])
		),
		'tariff' => $_GET['tariff']
	));

	// Moduł nie posiada metody validate_purchase_data
	if ($return_validation === FALSE)
		xml_output("bad_module", $lang['module_is_bad'], 0);

	// Są jakieś błędy przy sprawdzaniu danych
	if (isset($return_validation['data']['warnings'])) {
		$warnings = $extra_data = "";
		foreach ($return_validation['data']['warnings'] as $what => $text)
			$warnings .= "<strong>{$what}</strong><br />{$text}<br />";

		if (strlen($warnings))
			$extra_data .= "<warnings>{$warnings}</warnings>";

		xml_output($return_validation['status'], $return_validation['text'], $return_validation['positive'], $extra_data);
	}

	// Sprawdzanie danych przebiegło pomyślnie, więc przechodzimy do płatności
	$return_validation['purchase_data']['method'] = urldecode($_GET['method']);
	$return_validation['purchase_data']['sms_code'] = urldecode($_GET['sms_code']);
	$return_validation['purchase_data']['transaction_service'] = urldecode($_GET['transaction_service']);
	$return_payment = validate_payment($return_validation['purchase_data']);

	$extra_data = "";

	if (isset($return_payment['data']['bsid']))
		$extra_data .= "<bsid>{$return_payment['data']['bsid']}</bsid>";

	if (isset($return_payment['data']['warnings'])) {
		$warnings = "";
		foreach ($return_payment['data']['warnings'] as $what => $text)
			$warnings .= "<strong>{$what}</strong><br />{$text}<br />";

		if (strlen($warnings))
			$extra_data .= "<warnings>{$warnings}</warnings>";
	}

	xml_output($return_payment['status'], $return_payment['text'], $return_payment['positive'], $extra_data);
}