<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);
@set_time_limit(0);

define('IN_SCRIPT', "1");
define('SCRIPT_NAME', "ajax_install_full");

require_once "includes/global.php";
require_once SCRIPT_ROOT . "install/full/full.php";
