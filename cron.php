<?php

if (!defined("IN_SCRIPT")) {
    define('IN_SCRIPT', "1");
    define("SCRIPT_NAME", "cron");

    header("Content-type: text/html; charset=\"UTF-8\"");

    require_once "global.php";

    // Sprawdzenie random stringu
    if ($_GET['key'] != $settings['random_key'] && $argv[1] != $settings['random_key']) {
        exit($lang['wrong_cron_key']);
    }
}

// Usuwamy przestarzałe usługi graczy
delete_players_old_services();