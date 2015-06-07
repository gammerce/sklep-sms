<?php

define('IN_SCRIPT', "1");
define('SCRIPT_NAME', "index");

require_once "global.php";
$G_PID = $heart->page_exists($G_PID) ? $G_PID : "main_content";

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
parse_scripts_styles($scripts, $stylesheets);
eval("\$header = \"" . get_template("header") . "\";");

// Pobranie ostatecznego szablonu
eval("\$output = \"" . get_template("index") . "\";");

// Wyświetlenie strony
output_page($output);