<?php

define('IN_SCRIPT', "1");
define("SCRIPT_NAME", "servers_stuff");

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
if ($_GET['key'] != md5($settings['random_key']))
	exit;

$action = $_GET['action'];

if ($action == "purchase_service") {
	$output = "";

	if (($service_module = $heart->get_service_module(urldecode($_GET['service']))) === NULL)
		xml_output("bad_module", $lang->bad_module, 0);

	if (!object_implements($service_module, "IService_PurchaseOutside"))
		xml_output("bad_module", $lang->bad_module, 0);

	// Sprawdzamy dane zakupu
	$return_validation = $service_module->purchase_data_validate(new Entity_Purchase(array(
		'service' => $service_module->service['id'],
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
	)));

	// Są jakieś błędy przy sprawdzaniu danych
	if ($return_validation['status'] != "ok") {
		$extra_data = "";
		if (!empty($return_validation['data']['warnings'])) {
			$warnings = "";
			foreach ($return_validation['data']['warnings'] as $what => $warning)
				$warnings .= "<strong>{$what}</strong><br />" . implode("<br />", $warning) . "<br />";

			if (strlen($warnings))
				$extra_data .= "<warnings>{$warnings}</warnings>";
		}

		xml_output($return_validation['status'], $return_validation['text'], $return_validation['positive'], $extra_data);
	}

	$purchase = $return_validation['purchase'];
	$purchase->setPayment(array(
		'method' => urldecode($_GET['method']),
		'sms_code' => urldecode($_GET['sms_code']),
		'sms_service' => urldecode($_GET['transaction_service'])
	));
	$return_payment = validate_payment($purchase);

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

xml_output("script_error", "An error occured: no action.", false);