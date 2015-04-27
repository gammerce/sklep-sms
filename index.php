<?php

define('IN_SCRIPT', "1");
define('SCRIPT_NAME', "index");

require_once "global.php";
require_once SCRIPT_ROOT . "includes/functions_content.php";

// Pobieramy stronę
$page = $heart->get_page($G_PID);
$page_title = $page['title'];

// Dodanie stylów oraz skryptów uzywanych na danej stronie
if ($G_PID == "register") {
    $scripts[] = "<script type=\"text/javascript\" src=\"{$settings['shop_url_slash']}jscripts/register.js?version=" . VERSION . "\"></script>";
    $stylesheets[] = "<link href=\"{$settings['shop_url_slash']}styles/style_register.css?version=" . VERSION . "\" rel=\"stylesheet\" />";
} else if (in_array($G_PID, array("forgotten_password", "reset_password", "change_password"))) {
    $scripts[] = "<script type=\"text/javascript\" src=\"{$settings['shop_url_slash']}jscripts/modify_password.js?version=" . VERSION . "\"></script>";
} else if ($G_PID == "purchase") {
    $scripts[] = "<script type=\"text/javascript\" src=\"{$settings['shop_url_slash']}jscripts/purchase.js?version=" . VERSION . "\"></script>";
    $stylesheets[] = "<link href=\"{$settings['shop_url_slash']}styles/style_purchase.css?version=" . VERSION . "\" rel=\"stylesheet\" />";
} else if ($G_PID == "payment_log") {
    $stylesheets[] = "<link href=\"{$settings['shop_url_slash']}styles/style_payment_log.css?version=" . VERSION . "\" rel=\"stylesheet\" />";
} else if ($G_PID == "my_current_services") {
    $scripts[] = "<script type=\"text/javascript\" src=\"{$settings['shop_url_slash']}jscripts/my_current_services.js?version=" . VERSION . "\"></script>";
    $stylesheets[] = "<link href=\"{$settings['shop_url_slash']}styles/style_my_current_services.css?version=" . VERSION . "\" rel=\"stylesheet\" />";
} else if ($G_PID == "take_over_service") {
    $scripts[] = "<script type=\"text/javascript\" src=\"{$settings['shop_url_slash']}jscripts/take_over_service.js?version=" . VERSION . "\"></script>";
    $stylesheets[] = "<link href=\"{$settings['shop_url_slash']}styles/take_over_service.css?version=" . VERSION . "\" rel=\"stylesheet\" />";
}

// Pobranie miejsca logowania
$logged_info = get_content("logged_info");

// Pobranie portfela
$wallet = get_content("wallet");

// Pobranie zawartości
$content = get_content("content");

// Pobranie przycisków usług
$services_buttons = get_content("services_buttons");

// Pobranie przycisków użytkownika
$user_buttons = get_content("user_buttons");

// Pobranie headera
$scripts = implode("\n", array_unique($scripts));
$stylesheets = implode("\n", array_unique($stylesheets));
eval("\$header = \"" . get_template("header") . "\";");

// Pobranie ostatecznego szablonu
eval("\$output = \"" . get_template("index") . "\";");

// Wyświetlenie strony
output_page($output);