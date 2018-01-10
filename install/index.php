<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);

define('IN_SCRIPT', "1");
define('SCRIPT_NAME', "install");

require "includes/global.php";

if (is_installed()) {
    require_once SCRIPT_ROOT . "install/update/view.php";
} else {
    require_once SCRIPT_ROOT . "install/full/view.php";
}
