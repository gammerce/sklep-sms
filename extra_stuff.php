<?php

define('IN_SCRIPT', "1");
define("SCRIPT_NAME", "extra_stuff");

require_once "global.php";

// Jezeli jest popup, to wyswietl info w nowym oknie
if ($_GET['popup']) {
	// Usuwamy napis popup z linku
	$url = preg_replace('/' . preg_quote("&popup={$_GET['popup']}", '/') . '$/', '', $_SERVER['REQUEST_URI']);
	$output = create_dom_element("script", 'window.open("' . str_replace('"', '\"', $url) . '", "", "height=720,width=1280");', array(
		'type' => "text/javascript"
	));
	output_page($output);
}

$action = $_GET['action'];

switch ($action) {
	case "service_long_description":
		$output = "";

		if (($service_module = $heart->get_service_module($_GET['service'])) !== NULL)
			$output = $service_module->description_full_get();

		$heart->page_title = $lang->description . ": " . $service_module->service['name'];

		$heart->style_add($settings['shop_url_slash'] . "styles/extra_stuff/long_desc.css?version=" . VERSION);
		eval("\$header = \"" . get_template("header") . "\";");

		$output = create_dom_element("html", create_dom_element("head", $header) . create_dom_element("body", $output));
		output_page($output);
}