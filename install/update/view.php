<?php

if (!defined('IN_SCRIPT')) {
    exit;
}

require_once SCRIPT_ROOT . "install/update/includes/global.php";

$everything_ok = true;
// Pobieramy informacje o plikach ktore sa git i te ktore sa be
$update_info = update_info($everything_ok);
$class = $everything_ok ? "ok" : "bad";

// Pobranie ostatecznego szablonu
$output = eval($templates->install_update_render('index'));

// Wyświetlenie strony
output_page($output);